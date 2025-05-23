<?php

namespace App\Http\Controllers;

use App\Models\SpaceTrip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

// Esta clase se encarga de todo lo que tiene que ver con los viajes espaciales en la web
class SpaceTripController extends Controller {
    
    // Esta función sirve para crear un nuevo viaje espacial
    public function create(Request $request) {
        // Primero comprobamos que todos los datos que manda el usuario están bien
        $validated = $request->validate([
            'name' => 'required|string|max:255', // El nombre del viaje es obligatorio
            'company_id' => 'required|integer', // El id de la empresa es obligatorio
            'type' => 'required|string', // El tipo de viaje es obligatorio
            'photo' => 'required|file|image|max:2048', // Hay que subir una foto y no puede ser muy grande
            'departure' => 'required|date', // Fecha de salida
            'duration' => 'required|date', // Duración del viaje
            'capacity' => 'required|integer|min:1', // Cuántas plazas hay
            'price' => 'required|numeric|min:0', // Precio del viaje
            'description' => 'required|string' // Descripción del viaje
        ]);
    
        // Subimos la foto a ImgBB, que es una web para guardar imágenes
        $response = Http::asMultipart()
            ->post('https://api.imgbb.com/1/upload', [
                'key' => env('IMGBB_API_KEY'),
                'image' => fopen($request->file('photo')->path(), 'r'),
            ]);
    
        // Si la foto se sube bien, guardamos la dirección de la imagen y creamos el viaje
        if ($response->successful()) {
            $validated['photo'] = $response->json('data.url');
            $trip = SpaceTrip::create($validated);
            return response()->json($trip, 201); // Devolvemos el viaje creado
        }
    
        // Si hay un error subiendo la imagen, avisamos
        return response()->json(['error' => 'Error subiendo imagen'], 500);
    }                        

    // Esta función saca la lista de todos los viajes espaciales
    public function getFlights()
    {
        $flights = SpaceTrip::all();
        return response()->json($flights, 200);
    }

    // Esta función sirve para comprar un viaje
    public function purchase(Request $request, SpaceTrip $trip) {
        // Si no quedan plazas, avisamos
        if ($trip->capacity <= 0) {
            return response()->json(['error' => 'No hay capacidad disponible'], 400);
        }

        // Si hay plazas, decimos que la compra fue bien
        return response()->json(['success' => true, 'message' => "Has comprado el viaje {$trip->name}"]);
    }

    // Esta función sirve para actualizar los datos de un viaje
    public function update(Request $request, $id) {
        $trip = SpaceTrip::findOrFail($id);
        
        // Si el usuario sube una nueva foto, la subimos a ImgBB
        if ($request->hasFile('photo')) {
            $response = Http::asMultipart()
                ->post('https://api.imgbb.com/1/upload', [
                    'key' => env('IMGBB_API_KEY'),
                    'image' => fopen($request->file('photo')->path(), 'r'),
                ]);
    
            // Si la foto se sube bien, la guardamos
            if ($response->successful()) {
                $trip->photo = $response->json('data.url');
            } else {
                // Si hay error, avisamos
                return response()->json(['error' => 'Error actualizando imagen'], 500);
            }
        }
    
        $trip->save(); // Guardamos los cambios
        return response()->json($trip);
    }

    // Esta función borra un viaje espacial
    public function destroy($id)
    {
        $trip = SpaceTrip::findOrFail($id);
        
        // Si no se encuentra el viaje, avisamos
        if(!$trip) {
            return response()->json(['error' => 'Viaje no encontrado'], 404);
        }

        $trip->delete(); // Borramos el viaje
        return response()->noContent(); // No devolvemos nada, solo decimos que fue bien
    }
}