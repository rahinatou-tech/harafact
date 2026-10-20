<?php
// ═══════════════════════════════════════════════════════════
// app/Http/Middleware/CheckPermission.php
// Vérifie la permission de la route selon le rôle
// ═══════════════════════════════════════════════════════════
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        if (!$request->user()->peutFaire($permission)) {
            return response()->json([
                'message' => "Accès refusé. Vous n'avez pas la permission : {$permission}."
            ], 403);
        }

        return $next($request);
    }
}
