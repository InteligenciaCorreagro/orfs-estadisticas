<?php
// src/Controllers/Admin/TraderController.php

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Trader;
use App\Models\TraderAdicional;
use App\Models\AuditoriaLog;

class TraderController
{
    /**
     * Listar traders
     */
    public function index(Request $request): void
    {
        if ($request->isAjax()) {
            $traders = Trader::all();
            
            // Cargar adicionales de cada trader
            $data = array_map(function($trader) {
                return array_merge($trader->toArray(), [
                    'adicionales' => array_map(
                        fn($a) => $a->toArray(),
                        $trader->adicionales()
                    )
                ]);
            }, $traders);
            
            $response = new Response();
            $response->success('Traders obtenidos', $data);
        } else {
            Session::start();
            $userName = Session::get('user_name');
            $userRole = Session::get('user_role');
            
            $traders = Trader::all();
            
            ob_start();
            require __DIR__ . '/../../Views/admin/traders/index.php';
            $content = ob_get_clean();
            
            $response = new Response();
            $response->html($content);
        }
    }
    
    /**
     * Mostrar formulario de creación
     */
    public function create(Request $request): void
    {
        Session::start();
        $userName = Session::get('user_name');
        $userRole = Session::get('user_role');
        
        ob_start();
        require __DIR__ . '/../../Views/admin/traders/create.php';
        $content = ob_get_clean();
        
        $response = new Response();
        $response->html($content);
    }
    
    /**
     * Guardar nuevo trader
     */
    public function store(Request $request): void
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|min:3|unique:traders,nombre',
            'nit' => 'nullable',
            'porcentaje_comision' => 'required|numeric',
            'activo' => 'required|in:0,1'
        ]);
        
        if ($validator->failed()) {
            $response = new Response();
            $response->error('Datos inválidos', $validator->errors(), 422);
            return;
        }
        
        try {
            $trader = new Trader([
                'nombre' => $request->post('nombre'),
                'nit' => $request->post('nit'),
                'porcentaje_comision' => $request->post('porcentaje_comision'),
                'activo' => $request->post('activo')
            ]);
            $trader->save();
            
            // Guardar adicionales si existen
            $adicionales = $request->post('adicionales');
            if ($adicionales && is_array($adicionales)) {
                foreach ($adicionales as $nombreAdicional) {
                    if (!empty(trim($nombreAdicional))) {
                        $adicional = new TraderAdicional([
                            'trader_id' => $trader->id,
                            'nombre_adicional' => trim($nombreAdicional)
                        ]);
                        $adicional->save();
                    }
                }
            }
            
            // Auditoría
            $userId = Session::get('user_id');
            AuditoriaLog::registrar(
                $userId,
                'crear',
                'trader',
                'Trader creado: ' . $trader->nombre,
                null,
                $trader->toArray()
            );
            
            $response = new Response();
            $response->success('Trader creado correctamente', [
                'trader' => $trader->toArray()
            ]);
            
        } catch (\Exception $e) {
            $response = new Response();
            $response->error('Error al crear trader: ' . $e->getMessage(), [], 500);
        }
    }
    
    /**
     * Mostrar trader específico
     */
    public function show(Request $request, int $id): void
    {
        $trader = Trader::find($id);
        
        if (!$trader) {
            $response = new Response();
            $response->error('Trader no encontrado', [], 404);
            return;
        }
        
        $data = array_merge($trader->toArray(), [
            'adicionales' => array_map(
                fn($a) => $a->toArray(),
                $trader->adicionales()
            )
        ]);
        
        $response = new Response();
        $response->success('Trader obtenido', $data);
    }
    
    /**
     * Mostrar formulario de edición
     */
    public function edit(Request $request, int $id): void
    {
        $trader = Trader::find($id);
        
        if (!$trader) {
            Session::flash('error', 'Trader no encontrado');
            $response = new Response();
            $response->redirect('/admin/traders');
            return;
        }
        
        Session::start();
        $userName = Session::get('user_name');
        $userRole = Session::get('user_role');
        
        $adicionales = $trader->adicionales();
        
        ob_start();
        require __DIR__ . '/../../Views/admin/traders/edit.php';
        $content = ob_get_clean();
        
        $response = new Response();
        $response->html($content);
    }
    
    /**
     * Actualizar trader
     */
    public function update(Request $request, int $id): void
    {
        $trader = Trader::find($id);
        
        if (!$trader) {
            $response = new Response();
            $response->error('Trader no encontrado', [], 404);
            return;
        }
        
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|min:3',
            'nit' => 'nullable',
            'porcentaje_comision' => 'required|numeric',
            'activo' => 'required|in:0,1'
        ]);
        
        if ($validator->failed()) {
            $response = new Response();
            $response->error('Datos inválidos', $validator->errors(), 422);
            return;
        }
        
        try {
            $datosAnteriores = $trader->toArray();
            
            $trader->fill([
                'nombre' => $request->post('nombre'),
                'nit' => $request->post('nit'),
                'porcentaje_comision' => $request->post('porcentaje_comision'),
                'activo' => $request->post('activo')
            ]);
            $trader->save();
            
            // Actualizar adicionales
            // Primero eliminar todos los existentes
            foreach ($trader->adicionales() as $adicional) {
                $adicional->delete();
            }
            
            // Luego agregar los nuevos
            $adicionales = $request->post('adicionales');
            if ($adicionales && is_array($adicionales)) {
                foreach ($adicionales as $nombreAdicional) {
                    if (!empty(trim($nombreAdicional))) {
                        $adicional = new TraderAdicional([
                            'trader_id' => $trader->id,
                            'nombre_adicional' => trim($nombreAdicional)
                        ]);
                        $adicional->save();
                    }
                }
            }
            
            // Auditoría
            $userId = Session::get('user_id');
            AuditoriaLog::registrar(
                $userId,
                'editar',
                'trader',
                'Trader actualizado: ' . $trader->nombre,
                $datosAnteriores,
                $trader->toArray()
            );
            
            $response = new Response();
            $response->success('Trader actualizado correctamente', [
                'trader' => $trader->toArray()
            ]);
            
        } catch (\Exception $e) {
            $response = new Response();
            $response->error('Error al actualizar trader: ' . $e->getMessage(), [], 500);
        }
    }
    
    /**
     * Eliminar trader
     */
    public function delete(Request $request, int $id): void
    {
        $trader = Trader::find($id);
        
        if (!$trader) {
            $response = new Response();
            $response->error('Trader no encontrado', [], 404);
            return;
        }
        
        try {
            $datosAnteriores = $trader->toArray();
            
            // Eliminar adicionales
            foreach ($trader->adicionales() as $adicional) {
                $adicional->delete();
            }
            
            $nombreTrader = $trader->nombre;
            $trader->delete();
            
            // Auditoría
            $userId = Session::get('user_id');
            AuditoriaLog::registrar(
                $userId,
                'eliminar',
                'trader',
                'Trader eliminado: ' . $nombreTrader,
                $datosAnteriores,
                null
            );
            
            $response = new Response();
            $response->success('Trader eliminado correctamente');
            
        } catch (\Exception $e) {
            $response = new Response();
            $response->error('Error al eliminar trader: ' . $e->getMessage(), [], 500);
        }
    }
    
    /**
     * Listar traders activos (para selects)
     */
    public function activos(Request $request): void
    {
        $traders = Trader::activos();
        
        $data = array_map(fn($t) => $t->toArray(), $traders);
        
        $response = new Response();
        $response->success('Traders activos obtenidos', $data);
    }
}