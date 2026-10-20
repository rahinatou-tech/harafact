<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // ✅ Correct : le champ s'appelle est_actif (boolean)
        if (!$user->est_actif) {
            Auth::logout();
            return redirect()->route('login')
                   ->withErrors(['email' => 'Votre compte a été désactivé.']);
        }

        if (!in_array($user->role, $roles)) {
            abort(403, 'Accès refusé. Vous n\'avez pas les droits nécessaires.');
        }

        return $next($request);
    }
}