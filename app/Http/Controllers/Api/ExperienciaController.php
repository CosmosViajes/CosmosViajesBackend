<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Experiencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class ExperienciaController extends Controller
{
    public function index()
    {
        return Experiencia::orderBy('created_at', 'desc')->get();
    }

    public function store(Request $request)
    {
        $user = $request->user(); // Obtiene el usuario autenticado via token

        // Verifica si el usuario está autenticado
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $validated = $request->validate([
            'text' => 'required|string|max:500',
            'image' => 'nullable|string',
            'description' => 'nullable|string'
        ]);

        $experiencia = Experiencia::create([
            'user_id' => $user->id, // Usa el ID del usuario autenticado
            'userName' => $user->name,
            'text' => $validated['text'],
            'date' => now(),
            'image' => $validated['image'] ?? null,
            'description' => $validated['description'] ?? null
        ]);

        return response()->json($experiencia, 201);
    }

    public function destroy($id)
    {
        $experiencia = Experiencia::findOrFail($id);
        $experiencia->delete();
        return response()->json(null, 204);
    }

    public function uploadImage(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'required|string'
        ]);

        $user = $request->user();

        // Cambiar 'galeria' por 'profiles'
        $path = $request->file('image')->store('profiles', 'public');

        $experiencia = Experiencia::create([
            'user_id' => $user->id,
            'userName' => $user->name,
            'image' => asset(Storage::url($path)), // Generar URL completa
            'description' => $request->description,
            'date' => now()
        ]);

        return response()->json($experiencia);
    }

    // Método para eliminar imágenes
    public function deleteImage($id)
    {
        try {
            $experiencia = Experiencia::findOrFail($id);
            
            // Eliminar archivo físico
            if(Storage::disk('public')->exists($experiencia->image)) {
                Storage::disk('public')->delete($experiencia->image);
            }
            
            // Eliminar registro de la base de datos
            $experiencia->delete();

            return response()->json([
                'message' => 'Imagen eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar la imagen',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
