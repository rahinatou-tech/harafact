<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Utilisateur;

class ApiTokenMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();

        if (!$token) {
            return response()->json(['message' => 'Token manquant.'], 401);
        }

        $userId = cache()->get("access_token_{$token}");

        if (!$userId) {
            return response()->json(['message' => 'Non authentifié.'], 401);
        }

        $user = Utilisateur::find($userId);

        if (!$user) {
            return response()->json(['message' => 'Utilisateur introuvable.'], 401);
        }

        if (!$user->est_actif) {
            return response()->json(['message' => 'Compte désactivé.'], 403);
        }

        // ✅ Fix : setter le user sur la requête ET sur auth()
        $request->setUserResolver(fn() => $user);
        auth()->setUser($user);

        return $next($request);
    }
}