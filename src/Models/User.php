<?php
// src/Models/User.php

namespace App\Models;

use App\Core\Model;
use App\Core\Database;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    
    protected array $fillable = [
        'name',
        'email',
        'password',
        'role',
        'trader_name',
        'activo'
    ];
    
    protected array $hidden = [
        'password',
        'remember_token'
    ];
    
    protected array $casts = [
        'id' => 'int',
        'activo' => 'bool'
    ];
    
    public static function findByEmail(string $email): ?self
    {
        $sql = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $result = Database::fetch($sql, ['email' => $email]);

        if (!$result) {
            return null;
        }

        $user = new self();

        // CRÍTICO: Cargar TODOS los atributos incluyendo id
        foreach ($result as $key => $value) {
            $user->attributes[$key] = $value;
            $user->original[$key] = $value;
        }

        $user->castAttributes();

        return $user;
    }
    
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }
    
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }
    
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }
    
    public function isTrader(): bool
    {
        return $this->role === 'trader';
    }

    public function isBusinessIntelligence(): bool
    {
        return $this->role === 'business_intelligence';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }
}