<?php
// src/Controllers/Admin/CargaArchivoController.php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Services\Excel\ExcelReader;
use App\Services\Excel\RuedaProcessor;
use App\Models\CargaHistorial;
use App\Models\AuditoriaLog;
use Exception;

class CargaArchivoController
{
    private RuedaProcessor $processor;
    
    public function __construct()
    {
        $this->processor = new RuedaProcessor();
    }
    
    /**
     * Mostrar página de carga
     */
    public function index(Request $request): void
    {
        Session::start();
        
        $userName = Session::get('user_name');
        $userRole = Session::get('user_role');
        
        // Obtener historial reciente
        $historial = CargaHistorial::recientes(20);
        
        ob_start();
        require __DIR__ . '/../../Views/admin/carga-archivo.php';
        $content = ob_get_clean();
        
        $response = new Response();
        $response->html($content);
    }
    
    /**
     * Procesar archivo subido
     */
    public function upload(Request $request): void
    {
        // Validar que haya archivo
        if (!$request->hasFile('archivo')) {
            $response = new Response();
            $response->error('No se ha seleccionado ningún archivo', [], 400);
            return;
        }
        
        $file = $request->file('archivo');
        
        // Validar errores de subida
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $response = new Response();
            $response->error('Error al subir el archivo', [], 400);
            return;
        }
        
        // Validar extensión
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ['xls', 'xlsx'])) {
            $response = new Response();
            $response->error('Solo se permiten archivos .xls o .xlsx', [], 400);
            return;
        }
        
        // Validar tamaño (10MB)
        if ($file['size'] > 10485760) {
            $response = new Response();
            $response->error('El archivo no debe superar 10MB', [], 400);
            return;
        }
        
        try {
            // Mover archivo a storage
            $uploadDir = __DIR__ . '/../../../storage/uploads/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $filename = uniqid() . '_' . $file['name'];
            $filepath = $uploadDir . $filename;
            
            if (!move_uploaded_file($file['tmp_name'], $filepath)) {
                throw new Exception('Error al guardar el archivo');
            }
            
            // Leer Excel
            $reader = new ExcelReader($filepath);
            $data = $reader->toAssociativeArray();
            
            // Procesar archivo
            $resultado = $this->processor->procesarArchivo($data);
            
            // Determinar estado
            $estado = 'exitoso';
            if (!empty($resultado['errores'])) {
                $totalRuedas = count($resultado['ruedas_procesadas']) + count($resultado['errores']);
                $estado = count($resultado['errores']) === $totalRuedas ? 'fallido' : 'parcial';
            }
            
            // Guardar en historial
            $userId = Session::get('user_id');
            $historial = new CargaHistorial([
                'archivo_nombre' => $file['name'],
                'usuario_id' => $userId,
                'ruedas_procesadas' => json_encode($resultado['ruedas_procesadas']),
                'registros_insertados' => $resultado['total_registros'],
                'estado' => $estado,
                'mensaje' => $this->generarMensajeResultado($resultado)
            ]);
            $historial->save();
            
            // Auditoría
            AuditoriaLog::registrar(
                $userId,
                'carga_archivo',
                'admin',
                'Archivo procesado: ' . $file['name'],
                null,
                $resultado
            );
            
            // Eliminar archivo temporal
            unlink($filepath);
            
            $response = new Response();
            $response->success('Archivo procesado correctamente', [
                'resultado' => $resultado,
                'historial_id' => $historial->id
            ]);
            
        } catch (Exception $e) {
            // Guardar error en historial
            $userId = Session::get('user_id');
            $historial = new CargaHistorial([
                'archivo_nombre' => $file['name'],
                'usuario_id' => $userId,
                'ruedas_procesadas' => json_encode([]),
                'registros_insertados' => 0,
                'estado' => 'fallido',
                'mensaje' => $e->getMessage()
            ]);
            $historial->save();
            
            // Eliminar archivo temporal si existe
            if (isset($filepath) && file_exists($filepath)) {
                unlink($filepath);
            }
            
            $response = new Response();
            $response->error('Error al procesar archivo: ' . $e->getMessage(), [], 500);
        }
    }
    
    /**
     * Obtener historial de cargas
     */
    public function historial(Request $request): void
    {
        $limit = (int) $request->get('limit', 20);
        $historial = CargaHistorial::recientes($limit);
        
        // Cargar información de usuario
        $data = array_map(function($carga) {
            $usuario = $carga->usuario();
            return array_merge($carga->toArray(), [
                'usuario_nombre' => $usuario ? $usuario->name : 'Desconocido'
            ]);
        }, $historial);
        
        $response = new Response();
        $response->success('Historial obtenido', $data);
    }
    
    /**
     * Generar mensaje de resultado
     */
    private function generarMensajeResultado(array $resultado): string
    {
        $mensaje = "Ruedas procesadas: " . count($resultado['ruedas_procesadas']) . "\n";
        $mensaje .= "Total registros insertados: " . $resultado['total_registros'];
        
        if (!empty($resultado['errores'])) {
            $mensaje .= "\n\nErrores encontrados:\n";
            foreach ($resultado['errores'] as $error) {
                $mensaje .= "- Rueda {$error['rueda']}: {$error['error']}\n";
            }
        }
        
        return $mensaje;
    }
}