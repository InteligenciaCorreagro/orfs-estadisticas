<?php
// src/Controllers/Admin/UsuarioController.php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;
use App\Models\Trader;
use App\Models\AuditoriaLog;

class UsuarioController
{
    /**
     * Listar usuarios
     */
    public function index(Request $request): void
    {
        if ($request->isAjax()) {
            $users = User::all();

            $data = array_map(function ($user) {
                return $user->toArray();
            }, $users);

            $response = new Response();
            $response->success('Usuarios obtenidos', $data);
        } else {
            Session::start();
            $userName = Session::get('user_name');
            $userRole = Session::get('user_role');

            $users = User::all();

            ob_start();
            require __DIR__ . '/../../Views/admin/usuarios/index.php';
            $content = ob_get_clean();

            $response = new Response();
            $response->html($content);
        }
    }

    /**
     * Mostrar formulario de creación
     */
    public function create(Request $request): void
    {
        Session::start();
        $userName = Session::get('user_name');
        $userRole = Session::get('user_role');

        $traders = Trader::activos();

        ob_start();
        require __DIR__ . '/../../Views/admin/usuarios/create.php';
        $content = ob_get_clean();

        $response = new Response();
        $response->html($content);
    }

    /**
     * Guardar nuevo usuario
     */
    public function store(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'role' => 'required|in:admin,trader,guest',
            'trader_name' => 'nullable',
            'activo' => 'required|in:0,1'
        ]);

        if ($validator->failed()) {
            $response = new Response();
            $response->error('Datos inválidos', $validator->errors(), 422);
            return;
        }

        try {
            $user = new User([
                'name' => $request->post('name'),
                'email' => $request->post('email'),
                'role' => $request->post('role'),
                'trader_name' => $request->post('trader_name'),
                'activo' => $request->post('activo')
            ]);

            $user->setPassword($request->post('password'));
            $user->save();

            // Auditoría
            $userId = Session::get('user_id');
            AuditoriaLog::registrar(
                $userId,
                'crear',
                'usuario',
                'Usuario creado: ' . $user->name,
                null,
                $user->toArray()
            );

            $response = new Response();
            $response->success('Usuario creado correctamente', [
                'user' => $user->toArray()
            ]);
        } catch (\Exception $e) {
            $response = new Response();
            $response->error('Error al crear usuario: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Actualizar usuario
     */
    public function update(Request $request, int $id): void
    {
        $user = User::find($id);

        if (!$user) {
            $response = new Response();
            $response->error('Usuario no encontrado', [], 404);
            return;
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|min:3',
            'email' => 'required|email',
            'role' => 'required|in:admin,trader,guest',
            'trader_name' => 'nullable',
            'activo' => 'required|in:0,1'
        ]);

        if ($validator->failed()) {
            $response = new Response();
            $response->error('Datos inválidos', $validator->errors(), 422);
            return;
        }

        try {
            $datosAnteriores = $user->toArray();

            $user->fill([
                'name' => $request->post('name'),
                'email' => $request->post('email'),
                'role' => $request->post('role'),
                'trader_name' => $request->post('trader_name'),
                'activo' => $request->post('activo')
            ]);

            // Cambiar contraseña si se proporciona
            $newPassword = $request->post('password');
            if (!empty($newPassword)) {
                $user->setPassword($newPassword);
            }

            $user->save();

            // Auditoría
            $userId = Session::get('user_id');
            AuditoriaLog::registrar(
                $userId,
                'editar',
                'usuario',
                'Usuario actualizado: ' . $user->name,
                $datosAnteriores,
                $user->toArray()
            );

            $response = new Response();
            $response->success('Usuario actualizado correctamente', [
                'user' => $user->toArray()
            ]);
        } catch (\Exception $e) {
            $response = new Response();
            $response->error('Error al actualizar usuario: ' . $e->getMessage(), [], 500);
        }
    }

    /**
     * Eliminar usuario
     */
    public function delete(Request $request, int $id): void
    {
        $user = User::find($id);

        if (!$user) {
            $response = new Response();
            $response->error('Usuario no encontrado', [], 404);
            return;
        }

        // No permitir eliminar el propio usuario
        $currentUserId = Session::get('user_id');
        if ($currentUserId == $id) {
            $response = new Response();
            $response->error('No puedes eliminar tu propio usuario', [], 400);
            return;
        }

        try {
            $datosAnteriores = $user->toArray();
            $nombreUsuario = $user->name;

            $user->delete();

            // Auditoría
            AuditoriaLog::registrar(
                $currentUserId,
                'eliminar',
                'usuario',
                'Usuario eliminado: ' . $nombreUsuario,
                $datosAnteriores,
                null
            );

            $response = new Response();
            $response->success('Usuario eliminado correctamente');
        } catch (\Exception $e) {
            $response = new Response();
            $response->error('Error al eliminar usuario: ' . $e->getMessage(), [], 500);
        }
    }

    public function show(Request $request, int $id): void
    {
        $user = User::find($id);

        if (!$user) {
            $response = new Response();
            $response->error('Usuario no encontrado', [], 404);
            return;
        }

        $response = new Response();
        $response->success('Usuario obtenido', $user->toArray());
    }
}
