<?php
// src/Services/Excel/ExcelValidator.php

namespace App\Services\Excel;

use Exception;

class ExcelValidator
{
    private array $columnasRequeridas = [
        'ncodigo',
        'nnombre',
        'nomtrader',
        'comi_porce',
        'fecha',
        'rueda_no',
        'gtotal'
    ];
    
    private array $errores = [];
    
    public function validarEstructura(array $headers): bool
    {
        $this->errores = [];
        $headersCleaned = array_map('strtolower', array_map('trim', $headers));
        
        foreach ($this->columnasRequeridas as $columna) {
            if (!in_array(strtolower($columna), $headersCleaned)) {
                $this->errores[] = "Falta la columna requerida: '{$columna}'";
            }
        }
        
        return empty($this->errores);
    }
    
    public function validarDatos(array $data): bool
    {
        if (count($data) < 1) {
            $this->errores[] = 'El archivo no contiene datos para procesar';
            return false;
        }
        
        return true;
    }
    
    public function validarFila(array $row, int $lineNumber): bool
    {
        $erroresFila = [];
        
        // Validar NIT
        if (empty($row['ncodigo'])) {
            $erroresFila[] = "NIT vacío";
        }
        
        // Validar nombre
        if (empty($row['nnombre'])) {
            $erroresFila[] = "Nombre vacío";
        }
        
        // Validar trader
        if (empty($row['nomtrader'])) {
            $erroresFila[] = "Trader vacío";
        }
        
        // Validar fecha
        if (empty($row['fecha'])) {
            $erroresFila[] = "Fecha vacía";
        }
        
        // Validar rueda_no
        if (empty($row['rueda_no'])) {
            $erroresFila[] = "Número de rueda vacío";
        } elseif (!is_numeric($row['rueda_no'])) {
            $erroresFila[] = "Número de rueda inválido";
        }
        
        // Validar gtotal
        if (!isset($row['gtotal'])) {
            $erroresFila[] = "Gtotal vacío";
        } elseif (!is_numeric($row['gtotal'])) {
            $erroresFila[] = "Gtotal debe ser numérico";
        }
        
        if (!empty($erroresFila)) {
            $this->errores[] = "Línea {$lineNumber}: " . implode(', ', $erroresFila);
            return false;
        }
        
        return true;
    }
    
    public function extraerRuedas(array $data): array
    {
        $ruedas = [];
        
        foreach ($data as $row) {
            if (empty($row['rueda_no']) || empty($row['fecha'])) {
                continue;
            }
            
            $ruedaNo = (int) $row['rueda_no'];
            $fecha = $row['fecha'];
            
            if (!isset($ruedas[$ruedaNo])) {
                $ruedas[$ruedaNo] = $fecha;
            }
        }
        
        return $ruedas;
    }
    
    public function validarFechasPorRueda(array $data, array $ruedas): bool
    {
        foreach ($ruedas as $ruedaNo => $fechaEsperada) {
            foreach ($data as $row) {
                if ((int)($row['rueda_no'] ?? 0) === $ruedaNo) {
                    if (($row['fecha'] ?? '') !== $fechaEsperada) {
                        $this->errores[] = "La rueda {$ruedaNo} contiene múltiples fechas diferentes";
                        return false;
                    }
                }
            }
        }
        
        return true;
    }
    
    public function getErrores(): array
    {
        return $this->errores;
    }
    
    public function getErroresTexto(): string
    {
        return implode("\n", $this->errores);
    }
}