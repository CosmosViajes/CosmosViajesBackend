<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Experiencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;


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

    public function deleteImage($id)
    {
        $experiencia = Experiencia::findOrFail($id);

        $experiencia->image = null;
        $experiencia->save();

        return response()->json(['message' => 'Imagen eliminada correctamente.']);
    }

    public function uploadImage(Request $request) {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'required|string'
        ]);
    
        $user = $request->user();
        
        // Subir imagen a ImgBB
        $response = Http::asMultipart()
            ->post('https://api.imgbb.com/1/upload', [
                'key' => env('IMGBB_API_KEY'),
                'image' => fopen($request->file('image')->path(), 'r'),
            ]);
    
        if ($response->successful()) {
            $imageUrl = $response->json('data.url');
    
            $experiencia = Experiencia::create([
                'user_id' => $user->id,
                'userName' => $user->name,
                'image' => $imageUrl,
                'description' => $request->description,
                'date' => now()
            ]);
    
            return response()->json($experiencia);
        }
    
        return response()->json(['error' => 'Error subiendo imagen'], 500);
    }
}
