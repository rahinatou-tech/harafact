<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string ...$roles): mixed
    {
        if (!auth()->check()) {
            // API → JSON, Web → redirect
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Non authentifié.'], 401);
            }
            return redirect()->route('login');
        }
 
        if (!in_array(auth()->user()->role, $roles)) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json(['message' => 'Accès refusé.'], 403);
            }
            abort(403, 'Accès refusé. Vous n\'avez pas les permissions nécessaires.');
        }
 
        return $next($request);
    }
}