<?php
// src/Controllers/BusinessIntelligence/HistoricalDataController.php

namespace App\Controllers\BusinessIntelligence;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\BusinessIntelligence\HistoricalUploadService;

class HistoricalDataController extends Controller
{
    private HistoricalUploadService $uploadService;

    public function __construct()
    {
        $this->uploadService = new HistoricalUploadService();
    }

    /**
     * Mostrar dashboard de inteligencia de negocios
     */
    public function index(): void
    {
        Session::start();
        $user = auth();

        // Obtener historial de archivos subidos
        $uploads = $this->uploadService->getAllUploads();

        // Obtener estadísticas por año
        $yearStats = $this->uploadService->getYearlyStats();

        $data = [
            'user' => $user,
            'uploads' => $uploads,
            'yearStats' => $yearStats,
            'availableYears' => [2021, 2022, 2023, 2024, 2025]
        ];

        $this->render('business_intelligence/dashboard', $data);
    }

    /**
     * Subir archivo histórico
     */
    public function upload(Request $request): void
    {
        Session::start();
        $userId = Session::get('user_id');

        try {
            // Validar que se haya seleccionado un año
            $year = $request->post('year');
            if (empty($year)) {
                Session::flash('error', 'Debe seleccionar un año');
                redirect('/bi/dashboard');
                return;
            }

            // Validar que se haya subido un archivo
            if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                Session::flash('error', 'Debe seleccionar un archivo válido');
                redirect('/bi/dashboard');
                return;
            }

            $file = $_FILES['file'];

            // Validar extensión
            $allowedExtensions = ['xlsx', 'xls', 'csv'];
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($extension, $allowedExtensions)) {
                Session::flash('error', 'Solo se permiten archivos Excel (.xlsx, .xls) o CSV (.csv)');
                redirect('/bi/dashboard');
                return;
            }

            // Procesar la carga
            $notes = $request->post('notes', '');
            $result = $this->uploadService->uploadHistoricalFile(
                $file,
                (int)$year,
                $userId,
                $notes
            );

            if ($result['success']) {
                Session::flash('success', $result['message']);
            } else {
                Session::flash('error', $result['message']);
            }

        } catch (\Exception $e) {
            logError('Error uploading historical file: ' . $e->getMessage());
            Session::flash('error', 'Error al subir el archivo: ' . $e->getMessage());
        }

        redirect('/bi/dashboard');
    }

    /**
     * Eliminar archivo histórico
     */
    public function delete(Request $request): void
    {
        Session::start();
        $id = $request->get('id');

        try {
            $result = $this->uploadService->deleteUpload($id);

            if ($result['success']) {
                Session::flash('success', $result['message']);
            } else {
                Session::flash('error', $result['message']);
            }

        } catch (\Exception $e) {
            logError('Error deleting historical file: ' . $e->getMessage());
            Session::flash('error', 'Error al eliminar el archivo');
        }

        redirect('/bi/dashboard');
    }

    /**
     * Descargar archivo histórico
     */
    public function download(Request $request): void
    {
        $id = $request->get('id');

        try {
            $upload = $this->uploadService->getUploadById($id);

            if (!$upload) {
                Session::flash('error', 'Archivo no encontrado');
                redirect('/bi/dashboard');
                return;
            }

            $filePath = $upload['file_path'];

            if (!file_exists($filePath)) {
                Session::flash('error', 'El archivo físico no existe');
                redirect('/bi/dashboard');
                return;
            }

            // Descargar archivo
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $upload['original_filename'] . '"');
            header('Content-Length: ' . filesize($filePath));
            readfile($filePath);
            exit;

        } catch (\Exception $e) {
            logError('Error downloading historical file: ' . $e->getMessage());
            Session::flash('error', 'Error al descargar el archivo');
            redirect('/bi/dashboard');
        }
    }

    /**
     * API: Obtener estadísticas
     */
    public function getStats(Request $request): void
    {
        $year = $request->get('year');

        try {
            $stats = $this->uploadService->getStatsForYear($year);
            (new Response())->json($stats);

        } catch (\Exception $e) {
            (new Response())->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas'
            ], 500);
        }
    }
}
