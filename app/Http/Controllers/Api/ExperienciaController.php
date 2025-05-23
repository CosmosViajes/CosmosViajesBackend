<?php

// Aquí decimos en qué carpeta está este archivo
namespace App\Http\Controllers\Api;

// Estos son como "accesorios" que usamos en el archivo para que todo funcione
use App\Http\Controllers\Controller;
use App\Models\Experiencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

// Esta clase se encarga de todo lo que tiene que ver con las "experiencias" en la web
class ExperienciaController extends Controller
{
    // Esta función muestra todas las experiencias, poniendo primero las más nuevas
    public function index()
    {
        return Experiencia::orderBy('created_at', 'desc')->get();
    }

    // Esta función sirve para guardar una nueva experiencia que manda un usuario
    public function store(Request $request)
    {
        // Miramos quién es el usuario que está haciendo la petición
        $user = $request->user();

        // Si no hay usuario (no ha iniciado sesión), devolvemos un error
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        // Aquí comprobamos que los datos que manda el usuario están bien
        $validated = $request->validate([
            'text' => 'required|string|max:500',
            'image' => 'nullable|string',
            'description' => 'nullable|string'
        ]);

        // Guardamos la experiencia en la base de datos
        $experiencia = Experiencia::create([
            'user_id' => $user->id,
            'userName' => $user->name,
            'text' => $validated['text'],
            'date' => now(),
            'image' => $validated['image'] ?? null,
            'description' => $validated['description'] ?? null
        ]);

        // Devolvemos la experiencia guardada
        return response()->json($experiencia, 201);
    }

    // Esta función borra una experiencia por su id
    public function destroy($id)
    {
        $experiencia = Experiencia::findOrFail($id);
        $experiencia->delete();
        return response()->json(null, 204);
    }

    // Esta función borra solo la imagen de una experiencia
    public function deleteImage($id)
    {
        $experiencia = Experiencia::findOrFail($id);

        $experiencia->image = null;
        $experiencia->save();

        return response()->json(['message' => 'Imagen eliminada correctamente.']);
    }

    // Esta función sirve para subir una imagen a ImgBB y guardar la experiencia con la imagen
    public function uploadImage(Request $request) {
        // Comprobamos que la imagen y la descripción están bien
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'description' => 'required|string'
        ]);
    
        $user = $request->user();
        
        // Subimos la imagen a ImgBB (una web para guardar imágenes)
        $response = Http::asMultipart()
            ->post('https://api.imgbb.com/1/upload', [
                'key' => env('IMGBB_API_KEY'),
                'image' => fopen($request->file('image')->path(), 'r'),
            ]);
    
        // Si la subida fue bien, guardamos la experiencia con la imagen
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
    
        // Si hay un error al subir la imagen, avisamos
        return response()->json(['error' => 'Error subiendo imagen'], 500);
    }
}