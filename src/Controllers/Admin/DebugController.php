<?php
// src/Controllers/Admin/DebugController.php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\CargaHistorial;
use App\Models\Trader;
use App\Models\User;

class DebugController
{
    public function test(Request $request): void
    {
        $output = "<h1>Debug Information</h1>";
        $output .= "<style>body{font-family:monospace;padding:20px;} .success{color:green;} .error{color:red;}</style>";

        try {
            $output .= "<h2>1. Session Check</h2>";
            Session::start();
            $output .= "<p class='success'>✓ Session started</p>";

            $userName = Session::get('user_name');
            $output .= "<p class='success'>✓ User name: " . ($userName ?? 'NULL') . "</p>";

            $output .= "<h2>2. Database Models</h2>";

            // Test CargaHistorial
            try {
                $historial = CargaHistorial::recientes(5);
                $output .= "<p class='success'>✓ CargaHistorial::recientes() - " . count($historial) . " records</p>";
            } catch (\Exception $e) {
                $output .= "<p class='error'>✗ CargaHistorial ERROR: " . $e->getMessage() . "</p>";
                $output .= "<pre>" . $e->getTraceAsString() . "</pre>";
            }

            // Test Trader
            try {
                $traders = Trader::all();
                $output .= "<p class='success'>✓ Trader::all() - " . count($traders) . " records</p>";
            } catch (\Exception $e) {
                $output .= "<p class='error'>✗ Trader ERROR: " . $e->getMessage() . "</p>";
                $output .= "<pre>" . $e->getTraceAsString() . "</pre>";
            }

            // Test User
            try {
                $users = User::all();
                $output .= "<p class='success'>✓ User::all() - " . count($users) . " records</p>";
            } catch (\Exception $e) {
                $output .= "<p class='error'>✗ User ERROR: " . $e->getMessage() . "</p>";
                $output .= "<pre>" . $e->getTraceAsString() . "</pre>";
            }

            $output .= "<h2>3. View Loading Test</h2>";
            try {
                ob_start();
                require __DIR__ . '/../../Views/admin/test.php';
                $viewContent = ob_get_clean();
                $output .= "<p class='success'>✓ Test view loaded successfully</p>";
            } catch (\Exception $e) {
                $output .= "<p class='error'>✗ View ERROR: " . $e->getMessage() . "</p>";
            }

        } catch (\Exception $e) {
            $output .= "<p class='error'>FATAL ERROR: " . $e->getMessage() . "</p>";
            $output .= "<pre>" . $e->getTraceAsString() . "</pre>";
        }

        $response = new Response();
        $response->html($output);
    }
}
