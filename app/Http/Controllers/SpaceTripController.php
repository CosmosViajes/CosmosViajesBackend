<?php

namespace App\Http\Controllers;

use App\Models\SpaceTrip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SpaceTripController extends Controller {
    
    public function create(Request $request) {
    
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_id' => 'required|integer',
            'type' => 'required|string',
            'photo' => 'required|file|image|max:2048',
            'departure' => 'required|date',
            'duration' => 'required|date',
            'capacity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
            'description' => 'required|string'
        ]);
    
        // Subir imagen a ImgBB
        $response = Http::asMultipart()
            ->post('https://api.imgbb.com/1/upload', [
                'key' => env('IMGBB_API_KEY'),
                'image' => fopen($request->file('photo')->path(), 'r'),
            ]);
    
        if ($response->successful()) {
            $validated['photo'] = $response->json('data.url');
            $trip = SpaceTrip::create($validated);
            return response()->json($trip, 201);
        }
    
        return response()->json(['error' => 'Error subiendo imagen'], 500);
    }                        

    public function getFlights()
    {
        $flights = SpaceTrip::all();

        return response()->json($flights, 200);
    }

    public function purchase(Request $request, SpaceTrip $trip) {
        if ($trip->capacity <= 0) {
            return response()->json(['error' => 'No hay capacidad disponible'], 400);
        }

        // Aquí podrías implementar lógica para registrar la compra

        return response()->json(['success' => true, 'message' => "Has comprado el viaje {$trip->name}"]);
    }

    public function update(Request $request, $id) {
        $trip = SpaceTrip::findOrFail($id);
        
        if ($request->hasFile('photo')) {
            // Subir nueva imagen a ImgBB
            $response = Http::asMultipart()
                ->post('https://api.imgbb.com/1/upload', [
                    'key' => env('IMGBB_API_KEY'),
                    'image' => fopen($request->file('photo')->path(), 'r'),
                ]);
    
            if ($response->successful()) {
                $trip->photo = $response->json('data.url');
            } else {
                return response()->json(['error' => 'Error actualizando imagen'], 500);
            }
        }
    
        // Resto de la lógica de actualización...
        $trip->save();
    
        return response()->json($trip);
    }

    public function destroy($id)
    {
        $trip = SpaceTrip::findOrFail($id);
        
        if(!$trip) {
            return response()->json(['error' => 'Viaje no encontrado'], 404);
        }

        // Eliminar permanentemente
        $trip->delete();

        return response()->noContent();
    }
}