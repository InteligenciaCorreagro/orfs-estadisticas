<?php

namespace App\Core;

class Validator
{
    private array $data;
    private array $rules;
    private array $errors = [];
    private array $messages = [
        'required' => 'El campo :field es requerido',
        'email' => 'El campo :field debe ser un email válido',
        'min' => 'El campo :field debe tener al menos :param caracteres',
        'max' => 'El campo :field no debe superar :param caracteres',
        'numeric' => 'El campo :field debe ser numérico',
        'integer' => 'El campo :field debe ser un entero',
        'in' => 'El campo :field debe ser uno de: :param',
        'unique' => 'El :field ya está en uso',
        'exists' => 'El :field no existe',
        'confirmed' => 'La confirmación de :field no coincide',
    ];
    
    public function __construct(array $data, array $rules)
    {
        $this->data = $data;
        $this->rules = $rules;
    }
    
    public static function make(array $data, array $rules): self
    {
        return new self($data, $rules);
    }
    
    public function validate(): bool
    {
        foreach ($this->rules as $field => $rules) {
            $rulesArray = is_string($rules) ? explode('|', $rules) : $rules;
            
            foreach ($rulesArray as $rule) {
                $this->validateRule($field, $rule);
            }
        }
        
        return empty($this->errors);
    }
    
    private function validateRule(string $field, string $rule): void
    {
        [$ruleName, $param] = $this->parseRule($rule);
        $value = $this->data[$field] ?? null;
        
        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, $ruleName);
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, $ruleName);
                }
                break;
                
            case 'min':
                if (!empty($value) && strlen($value) < (int)$param) {
                    $this->addError($field, $ruleName, $param);
                }
                break;
                
            case 'max':
                if (!empty($value) && strlen($value) > (int)$param) {
                    $this->addError($field, $ruleName, $param);
                }
                break;
                
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    $this->addError($field, $ruleName);
                }
                break;
                
            case 'integer':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    $this->addError($field, $ruleName);
                }
                break;
                
            case 'in':
                $options = explode(',', $param);
                if (!empty($value) && !in_array($value, $options)) {
                    $this->addError($field, $ruleName, $param);
                }
                break;
                
            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if ($value !== ($this->data[$confirmField] ?? null)) {
                    $this->addError($field, $ruleName);
                }
                break;
                
            case 'unique':
                [$table, $column] = explode(',', $param);
                $exists = Database::fetch(
                    "SELECT id FROM {$table} WHERE {$column} = :value LIMIT 1",
                    ['value' => $value]
                );
                if ($exists) {
                    $this->addError($field, $ruleName);
                }
                break;
                
            case 'exists':
                [$table, $column] = explode(',', $param);
                $exists = Database::fetch(
                    "SELECT id FROM {$table} WHERE {$column} = :value LIMIT 1",
                    ['value' => $value]
                );
                if (!$exists) {
                    $this->addError($field, $ruleName);
                }
                break;
        }
    }
    
    private function parseRule(string $rule): array
    {
        if (strpos($rule, ':') !== false) {
            [$name, $param] = explode(':', $rule, 2);
            return [$name, $param];
        }
        return [$rule, null];
    }
    
    private function addError(string $field, string $rule, ?string $param = null): void
    {
        $message = str_replace(':field', $field, $this->messages[$rule] ?? 'Validación fallida');
        if ($param) {
            $message = str_replace(':param', $param, $message);
        }
        
        $this->errors[$field][] = $message;
    }
    
    public function errors(): array
    {
        return $this->errors;
    }
    
    public function failed(): bool
    {
        return !empty($this->errors);
    }
}