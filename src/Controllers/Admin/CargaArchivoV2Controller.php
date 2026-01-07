<?php
// src/Controllers/Admin/CargaArchivoV2Controller.php
// VERSIÓN 2 - ARCHIVO NUEVO PARA EVITAR CACHE

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Excel\ExcelReader;
use App\Services\Excel\RuedaProcessorV2;
use App\Models\CargaHistorial;
use App\Models\AuditoriaLog;
use Exception;

class CargaArchivoV2Controller
{
    private RuedaProcessorV2 $processor;

    public function __construct()
    {
        $this->processor = new RuedaProcessorV2();
    }

    public function index(Request $request): void
    {
        Session::start();

        $userName = Session::get('user_name');
        $userRole = Session::get('user_role');

        $historial = CargaHistorial::recientes(20);

        ob_start();
        require __DIR__ . '/../../Views/admin/carga-archivo-v2.php';
        $content = ob_get_clean();

        $response = new Response();
        $response->html($content);
    }

    public function upload(Request $request): void
    {
        $debugFile = __DIR__ . '/../../../public/debug_v2.txt';

        file_put_contents($debugFile,
            "=== NUEVA CARGA V2 ===\n" .
            "Timestamp: " . date('Y-m-d H:i:s') . "\n\n"
        );

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

            file_put_contents($debugFile,
                "Archivo guardado: {$filename}\n" .
                "Leyendo Excel...\n",
                FILE_APPEND
            );

            // Leer Excel
            $reader = new ExcelReader($filepath);
            $data = $reader->toAssociativeArray();

            file_put_contents($debugFile,
                "Excel leído: " . count($data) . " filas\n" .
                "Procesando archivo...\n\n",
                FILE_APPEND
            );

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

            file_put_contents($debugFile,
                "\n✓ PROCESO COMPLETADO EXITOSAMENTE\n",
                FILE_APPEND
            );

            $response = new Response();
            $response->success('Archivo procesado correctamente', [
                'resultado' => $resultado,
                'historial_id' => $historial->id
            ]);

        } catch (Exception $e) {
            file_put_contents($debugFile,
                "\n✗ ERROR: " . $e->getMessage() . "\n" .
                "Trace: " . $e->getTraceAsString() . "\n",
                FILE_APPEND
            );

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

    public function historial(Request $request): void
    {
        $limit = (int) $request->get('limit', 20);
        $historial = CargaHistorial::recientes($limit);

        $data = array_map(function($carga) {
            $usuario = $carga->usuario();
            return array_merge($carga->toArray(), [
                'usuario_nombre' => $usuario ? $usuario->name : 'Desconocido'
            ]);
        }, $historial);

        $response = new Response();
        $response->success('Historial obtenido', $data);
    }

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
