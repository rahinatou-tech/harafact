<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsEmploye
{
    public function handle(Request $request, Closure $next)
    {
        // Vérifier si authentifié
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // Vérifier le rôle
        $role = auth()->user()->role;
        
        // Si pas Employe, bloquer
        if ($role !== 'Employe') {
            abort(403, "Accès refusé. Rôle actuel: {$role}");
        }

        return $next($request);
    }
}