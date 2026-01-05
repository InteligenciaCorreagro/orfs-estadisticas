<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class AuthMiddleware
{
    public function handle(Request $request): void
    {
        Session::start();
        
        if (!Session::has('user_id')) {
            if ($request->isAjax()) {
                $response = new Response();
                $response->error('No autenticado', [], 401);
            } else {
                $response = new Response();
                $response->redirect('/login');
            }
        }
    }
}