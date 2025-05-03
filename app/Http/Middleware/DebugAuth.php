<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DebugAuth {
    public function handle(Request $request, Closure $next) {
        // Verificar si hay un token en el header
        $token = $request->header('Authorization');
        if (!$token) {
            \Log::error('Token no proporcionado.');
            return response()->json(['error' => 'Token no proporcionado'], 401);
        }

        // Eliminar "Bearer " del token
        $token = str_replace('Bearer ', '', $token);

        // Decodificar el token manualmente
        try {
            $user = auth()->user();
            if (!$user) {
                \Log::error('Usuario no autenticado.');
                return response()->json(['error' => 'Usuario no autenticado'], 401);
            }

            \Log::info('Usuario autenticado:', ['id' => $user->id, 'email' => $user->email]);
        } catch (\Exception $e) {
            \Log::error('Error al decodificar token:', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Token inválido'], 401);
        }

        return $next($request);
    }
}