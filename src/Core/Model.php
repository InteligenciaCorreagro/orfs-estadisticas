<?php
// src/Core/Model.php

namespace App\Core;

use App\Core\Database;

abstract class Model
{
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $hidden = [];
    protected array $casts = [];
    
    private array $attributes = [];
    private array $original = [];
    
    public function __construct(array $attributes = [])
    {
        $this->fill($attributes);
    }
    
    // MÉTODO CRÍTICO - CORREGIDO
    public static function find($id): ?self
    {
        $instance = new static();
        
        $sql = "SELECT * FROM {$instance->table} WHERE {$instance->primaryKey} = :id LIMIT 1";
        $result = Database::fetch($sql, ['id' => $id]);
        
        if (!$result) {
            return null;
        }
        
        $model = new static();
        
        // CRÍTICO: Cargar TODOS los campos incluyendo el ID
        foreach ($result as $key => $value) {
            $model->attributes[$key] = $value;
            $model->original[$key] = $value;
        }
        
        $model->castAttributes();
        
        return $model;
    }
    
    public static function all(): array
    {
        $instance = new static();
        
        $sql = "SELECT * FROM {$instance->table}";
        $results = Database::fetchAll($sql);
        
        $models = [];
        foreach ($results as $result) {
            $model = new static();
            foreach ($result as $key => $value) {
                $model->attributes[$key] = $value;
                $model->original[$key] = $value;
            }
            $model->castAttributes();
            $models[] = $model;
        }
        
        return $models;
    }
    
    public static function where(string $column, $value): array
    {
        $instance = new static();
        
        $sql = "SELECT * FROM {$instance->table} WHERE {$column} = :value";
        $results = Database::fetchAll($sql, ['value' => $value]);
        
        $models = [];
        foreach ($results as $result) {
            $model = new static();
            foreach ($result as $key => $value) {
                $model->attributes[$key] = $value;
                $model->original[$key] = $value;
            }
            $model->castAttributes();
            $models[] = $model;
        }
        
        return $models;
    }
    
    public function fill(array $attributes): self
    {
        foreach ($attributes as $key => $value) {
            if (in_array($key, $this->fillable)) {
                $this->attributes[$key] = $value;
            }
        }
        
        return $this;
    }
    
    public function save(): bool
    {
        if (isset($this->attributes[$this->primaryKey]) && !empty($this->attributes[$this->primaryKey])) {
            return $this->update();
        }
        
        return $this->insert();
    }
    
    private function insert(): bool
    {
        $fillableData = array_intersect_key($this->attributes, array_flip($this->fillable));
        
        $columns = array_keys($fillableData);
        $values = array_values($fillableData);
        
        $placeholders = array_map(fn($col) => ":$col", $columns);
        
        $sql = sprintf(
            "INSERT INTO %s (%s) VALUES (%s)",
            $this->table,
            implode(', ', $columns),
            implode(', ', $placeholders)
        );
        
        $params = array_combine($columns, $values);
        
        $id = Database::insert($sql, $params);
        
        if ($id) {
            $this->attributes[$this->primaryKey] = $id;
            $this->original = $this->attributes;
            return true;
        }
        
        return false;
    }
    
    private function update(): bool
    {
        $fillableData = array_intersect_key($this->attributes, array_flip($this->fillable));
        
        $setParts = [];
        foreach (array_keys($fillableData) as $column) {
            $setParts[] = "$column = :$column";
        }
        
        $sql = sprintf(
            "UPDATE %s SET %s WHERE %s = :primary_key_value",
            $this->table,
            implode(', ', $setParts),
            $this->primaryKey
        );
        
        $params = $fillableData;
        $params['primary_key_value'] = $this->attributes[$this->primaryKey];
        
        return Database::update($sql, $params);
    }
    
    public function delete(): bool
    {
        if (!isset($this->attributes[$this->primaryKey])) {
            return false;
        }
        
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        
        return Database::delete($sql, ['id' => $this->attributes[$this->primaryKey]]);
    }
    
    protected function castAttributes(): void
    {
        foreach ($this->casts as $key => $type) {
            if (!isset($this->attributes[$key])) {
                continue;
            }
            
            $value = $this->attributes[$key];
            
            $this->attributes[$key] = match($type) {
                'int', 'integer' => (int) $value,
                'float', 'double' => (float) $value,
                'bool', 'boolean' => (bool) $value,
                'string' => (string) $value,
                'array', 'json' => is_string($value) ? json_decode($value, true) : $value,
                'datetime' => $value,
                default => $value
            };
        }
    }
    
    public function toArray(): array
    {
        $array = $this->attributes;
        
        foreach ($this->hidden as $hidden) {
            unset($array[$hidden]);
        }
        
        return $array;
    }
    
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }
    
    // CRÍTICO: Magic methods para acceder a atributos
    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }
    
    public function __set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }
    
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
    
    protected static function getDatabase(): Database
    {
        return Database::class;
    }
}