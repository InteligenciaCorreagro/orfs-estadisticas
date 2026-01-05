<?php
// src/Services/Excel/RuedaProcessor.php

namespace App\Services\Excel;

use App\Core\Database;
use App\Models\OrfsTransaction;
use App\Models\Trader;
use DateTime;
use Exception;

class RuedaProcessor
{
    private ExcelValidator $validator;
    private array $meses = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo',
        4 => 'Abril', 5 => 'Mayo', 6 => 'Junio',
        7 => 'Julio', 8 => 'Agosto', 9 => 'Septiembre',
        10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
    ];
    
    public function __construct()
    {
        $this->validator = new ExcelValidator();
    }
    
    public function procesarArchivo(array $data): array
    {
        // Validar estructura
        if (!$this->validator->validarEstructura(array_keys($data[0] ?? []))) {
            throw new Exception(
                "Estructura de archivo inválida:\n" . 
                $this->validator->getErroresTexto()
            );
        }
        
        // Validar datos
        if (!$this->validator->validarDatos($data)) {
            throw new Exception(
                "Datos inválidos:\n" . 
                $this->validator->getErroresTexto()
            );
        }
        
        // Extraer ruedas
        $ruedas = $this->validator->extraerRuedas($data);
        
        if (empty($ruedas)) {
            throw new Exception("No se encontraron ruedas para procesar");
        }
        
        // Validar fechas por rueda
        if (!$this->validator->validarFechasPorRueda($data, $ruedas)) {
            throw new Exception(
                "Error en validación de fechas:\n" . 
                $this->validator->getErroresTexto()
            );
        }
        
        $resultado = [
            'ruedas_procesadas' => [],
            'total_registros' => 0,
            'errores' => []
        ];
        
        // Procesar cada rueda
        foreach ($ruedas as $ruedaNo => $fecha) {
            try {
                Database::beginTransaction();
                
                $registrosProcesados = $this->procesarRueda($data, $ruedaNo);
                
                Database::commit();
                
                $resultado['ruedas_procesadas'][] = [
                    'rueda' => $ruedaNo,
                    'fecha' => $fecha,
                    'registros' => $registrosProcesados
                ];
                $resultado['total_registros'] += $registrosProcesados;
                
            } catch (Exception $e) {
                Database::rollback();
                $resultado['errores'][] = [
                    'rueda' => $ruedaNo,
                    'error' => $e->getMessage()
                ];
            }
        }
        
        return $resultado;
    }
    
    private function procesarRueda(array $data, int $ruedaNo): int
    {
        // Eliminar datos anteriores de esta rueda
        OrfsTransaction::eliminarPorRueda($ruedaNo);
        
        $registrosInsertados = 0;
        
        foreach ($data as $index => $row) {
            // Filtrar solo registros de esta rueda
            if ((int)($row['rueda_no'] ?? 0) !== $ruedaNo) {
                continue;
            }
            
            // Validar fila
            if (!$this->validator->validarFila($row, $index + 2)) {
                continue; // Saltar fila inválida
            }
            
            // Procesar y guardar registro
            $registro = $this->procesarRegistro($row);
            
            Database::insert('orfs_transactions', $registro);
            $registrosInsertados++;
        }
        
        return $registrosInsertados;
    }
    
    private function procesarRegistro(array $row): array
    {
        $nit = trim($row['ncodigo']);
        $nombreTrader = trim($row['nomtrader']);
        $gtotal = (float) ($row['gtotal'] ?? 0);
        
        // Obtener porcentaje de comisión
        $porcentajeComision = $this->obtenerPorcentajeComision($nombreTrader, $nit);
        
        // Calcular comisión
        $comisionCorr = $gtotal * ($porcentajeComision / 100);
        
        // Parsear fecha
        $fecha = $this->parsearFecha($row['fecha']);
        
        // Obtener nombre del mes
        $nombreMes = $this->meses[(int)$fecha->format('n')];
        
        return [
            'reasig' => null,
            'nit' => $nit,
            'nombre' => trim($row['nnombre'] ?? ''),
            'corredor' => $nombreTrader,
            'comi_porcentual' => (float) ($row['comi_porce'] ?? 0),
            'ciudad' => trim($row['nomzona'] ?? ''),
            'fecha' => $fecha->format('Y-m-d'),
            'rueda_no' => (int) $row['rueda_no'],
            'negociado' => $gtotal,
            'comi_bna' => 0,
            'campo_209' => 0,
            'comi_corr' => $comisionCorr,
            'iva_bna' => 0,
            'iva_comi' => 0,
            'iva_cama' => 0,
            'facturado' => 0,
            'mes' => $nombreMes,
            'comi_corr_neto' => $comisionCorr,
            'year' => (int) $fecha->format('Y'),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    private function obtenerPorcentajeComision(string $nombreTrader, string $nit): float
    {
        $trader = Trader::buscarPorNombreONit($nombreTrader);
        
        if ($trader) {
            return (float) $trader->porcentaje_comision;
        }
        
        // Si no encuentra, buscar por NIT
        $trader = Trader::buscarPorNombreONit($nit);
        
        if ($trader) {
            return (float) $trader->porcentaje_comision;
        }
        
        return 0.0;
    }
    
    private function parsearFecha($fecha): DateTime
    {
        try {
            // Si es número (serial date de Excel)
            if (is_numeric($fecha)) {
                $unixDate = ($fecha - 25569) * 86400;
                return new DateTime('@' . $unixDate);
            }
            
            // Intentar diferentes formatos
            $formatos = ['d/m/Y', 'Y-m-d', 'd-m-Y', 'm/d/Y'];
            
            foreach ($formatos as $formato) {
                $date = DateTime::createFromFormat($formato, $fecha);
                if ($date !== false) {
                    return $date;
                }
            }
            
            // Intento genérico
            return new DateTime($fecha);
            
        } catch (Exception $e) {
            throw new Exception("Formato de fecha inválido: {$fecha}");
        }
    }
}