<?php
// src/Services/Auth/AuthService.php

namespace App\Services\Auth;

use App\Core\Session;
use App\Models\User;
use App\Models\AuditoriaLog;

class AuthService
{
    /**
     * Intentar login
     */
    public function login(string $email, string $password): array
    {
        $user = User::findByEmail($email);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Credenciales inválidas'
            ];
        }

        if (!$user->activo) {
            return [
                'success' => false,
                'message' => 'Usuario desactivado'
            ];
        }

        if (!$user->verifyPassword($password)) {
            return [
                'success' => false,
                'message' => 'Credenciales inválidas'
            ];
        }

        // CRÍTICO: Asegurar que la sesión se inicie ANTES de guardar datos
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Crear sesión
        Session::regenerate();
        Session::set('user_id', $user->id);
        Session::set('user_name', $user->name);
        Session::set('user_email', $user->email);
        Session::set('user_role', $user->role);
        Session::set('trader_name', $user->trader_name);

        // Registrar en auditoría
        AuditoriaLog::registrar(
            $user->id,
            'login',
            'auth',
            'Usuario inició sesión'
        );

        return [
            'success' => true,
            'message' => 'Login exitoso',
            'user' => $user->toArray()
        ];
    }

    /**
     * Cerrar sesión
     */
    public function logout(): void
    {
        $userId = Session::get('user_id');

        if ($userId) {
            AuditoriaLog::registrar(
                $userId,
                'logout',
                'auth',
                'Usuario cerró sesión'
            );
        }

        Session::destroy();
    }

    /**
     * Verificar si está autenticado
     */
    public function check(): bool
    {
        return Session::has('user_id');
    }

    /**
     * Obtener usuario actual
     */
    public function user(): ?User
    {
        $userId = Session::get('user_id');

        if (!$userId) {
            return null;
        }

        return User::find($userId);
    }

    /**
     * Verificar rol
     */
    public function hasRole(string $role): bool
    {
        $userRole = Session::get('user_role');
        return $userRole === $role;
    }

    /**
     * Verificar si es admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Verificar si es trader
     */
    public function isTrader(): bool
    {
        return $this->hasRole('trader');
    }

    /**
     * Cambiar contraseña
     */
    public function changePassword(int $userId, string $currentPassword, string $newPassword): array
    {
        $user = User::find($userId);

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado'
            ];
        }

        if (!$user->verifyPassword($currentPassword)) {
            return [
                'success' => false,
                'message' => 'Contraseña actual incorrecta'
            ];
        }

        $user->setPassword($newPassword);
        $user->save();

        AuditoriaLog::registrar(
            $userId,
            'cambio_password',
            'auth',
            'Usuario cambió su contraseña'
        );

        return [
            'success' => true,
            'message' => 'Contraseña actualizada correctamente'
        ];
    }
}
