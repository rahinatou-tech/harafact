<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsAdmin
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $role = auth()->user()->role;
        
        // Admin + DG (lecture seule)
        if ($role !== 'Admin' && $role !== 'DG') {
            abort(403, 'Accès refusé.');
        }

        return $next($request);
    }
}