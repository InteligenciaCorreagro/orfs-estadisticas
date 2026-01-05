<?php

namespace App\Middleware;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;

class RoleMiddleware
{
    private array $allowedRoles;
    
    public function __construct(array $allowedRoles)
    {
        $this->allowedRoles = $allowedRoles;
    }
    
    public function handle(Request $request): void
    {
        Session::start();
        
        $userRole = Session::get('user_role');
        
        if (!in_array($userRole, $this->allowedRoles)) {
            if ($request->isAjax()) {
                $response = new Response();
                $response->error('No autorizado', [], 403);
            } else {
                $response = new Response();
                $response->redirect('/unauthorized');
            }
        }
    }
}