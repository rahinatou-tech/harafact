<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class IsComptable
{
    public function handle(Request $request, Closure $next)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        if (auth()->user()->role !== 'Comptable' && auth()->user()->role !== 'DG') {
            abort(403, 'Accès refusé. Seuls les comptables peuvent accéder à cette ressource.');
        }

        return $next($request);
    }
}