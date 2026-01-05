<?php
// src/Controllers/AuthController.php

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Core\Session;
use App\Services\Auth\AuthService;

class AuthController
{
    private AuthService $authService;

    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * Mostrar formulario de login
     */
    public function showLogin(Request $request): void
    {
        Session::start();

        // Si ya está autenticado, redirigir al dashboard
        if ($this->authService->check()) {
            $response = new Response();
            $response->redirect('/dashboard');
            return;
        }

        $flashMessage = Session::getFlash('message');
        $flashError = Session::getFlash('error');

        // RUTA CORREGIDA - Desde la raíz del proyecto
        $viewPath = __DIR__ . '/../../src/Views/auth/login.php';

        if (!file_exists($viewPath)) {
            die("Error: Vista no encontrada en: $viewPath");
        }

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        $response = new Response();
        $response->html($content);
    }

    /**
     * Procesar login
     */
    public function login(Request $request): void
    {
        Session::start(); // IMPORTANTE: Iniciar sesión antes de validar

        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        if ($validator->failed()) {
            if ($request->isAjax()) {
                $response = new Response();
                $response->error('Datos inválidos', $validator->errors(), 422);
            } else {
                Session::flash('error', 'Por favor complete todos los campos correctamente');
                $response = new Response();
                $response->redirect('/login');
            }
            return;
        }

        $email = $request->post('email');
        $password = $request->post('password');

        $result = $this->authService->login($email, $password);

        if ($result['success']) {
            // Login exitoso - IMPORTANTE: Guardar sesión antes de redirigir
            session_write_close();

            // Redirigir al dashboard
            $response = new Response();
            $response->redirect('/dashboard');
        } else {
            // Login fallido
            if ($request->isAjax()) {
                $response = new Response();
                $response->error($result['message'], [], 401);
            } else {
                Session::flash('error', $result['message']);
                $response = new Response();
                $response->redirect('/login');
            }
        }
    }
    
    /**
     * Cerrar sesión
     */
    public function logout(Request $request): void
    {
        $this->authService->logout();

        if ($request->isAjax()) {
            $response = new Response();
            $response->success('Sesión cerrada correctamente');
        } else {
            $response = new Response();
            $response->redirect('/login');
        }
    }

    /**
     * Obtener usuario actual (API)
     */
    public function me(Request $request): void
    {
        $user = $this->authService->user();

        if (!$user) {
            $response = new Response();
            $response->error('No autenticado', [], 401);
            return;
        }

        $response = new Response();
        $response->success('Usuario actual', $user->toArray());
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:6',
            'confirm_password' => 'required|confirmed'
        ]);

        if ($validator->failed()) {
            $response = new Response();
            $response->error('Datos inválidos', $validator->errors(), 422);
            return;
        }

        $userId = Session::get('user_id');

        $result = $this->authService->changePassword(
            $userId,
            $request->post('current_password'),
            $request->post('new_password')
        );

        $response = new Response();
        if ($result['success']) {
            $response->success($result['message']);
        } else {
            $response->error($result['message'], [], 400);
        }
    }
}
