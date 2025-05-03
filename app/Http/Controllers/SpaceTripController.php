<?php

namespace App\Http\Controllers;

use App\Models\SpaceTrip;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
    
        $path = $request->file('photo')->store('trips', 'public');
        $validated['photo'] = asset(Storage::url($path));

        $trip = SpaceTrip::create($validated);
    
        return response()->json($trip, 201);
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

    public function update(Request $request, $id)
    {
        $trip = SpaceTrip::findOrFail($id);

        $rules = [];
        $validated = [];

        // Definir reglas de validación dinámicas
        if ($request->has('name')) $rules['name'] = 'string|max:255';
        if ($request->has('type')) $rules['type'] = 'in:Orbital,Suborbital,Lunar,Espacial';
        if ($request->has('departure')) $rules['departure'] = 'date';
        if ($request->has('duration')) $rules['duration'] = 'date|after:departure';
        if ($request->has('capacity')) $rules['capacity'] = 'integer|min:1';
        if ($request->has('price')) $rules['price'] = 'numeric|min:0';
        if ($request->has('description')) $rules['description'] = 'string';

        // Manejo de la imagen
        if ($request->hasFile('photo')) {
            $rules['photo'] = 'required|image|mimes:jpeg,png,jpg,gif|max:2048';
            $validatedPhoto = $request->validate($rules);
            
            // Eliminar imagen anterior
            if ($trip->photo) {
                $oldPath = str_replace(asset(''), '', $trip->photo); // Eliminar dominio
                $oldPath = str_replace('storage/', '', $oldPath); // Eliminar prefijo storage/
                Storage::disk('public')->delete($oldPath);
            }

            // Guardar nueva imagen
            $path = $request->file('photo')->store('trips', 'public');
            $trip->photo = asset('storage/' . $path); // URL completa con asset
        }

        // Validar campos restantes
        $validated = $request->validate($rules);

        // Actualizar campos
        foreach ($validated as $field => $value) {
            if ($field !== 'photo') {
                $trip->$field = $value;
            }
        }

        $trip->save();

        return response()->json([
            'trip' => $trip,
            'message' => 'Viaje actualizado correctamente'
        ]);
    }

    public function destroy($id)
    {
        $trip = SpaceTrip::findOrFail($id);
        
        if(!$trip) {
            return response()->json(['error' => 'Viaje no encontrado'], 404);
        }

        // Eliminar imagen si existe
        if ($trip->photo) {
            $photoPath = str_replace(Storage::url(''), '', $trip->photo);
            Storage::disk('public')->delete($photoPath);
        }

        // Eliminar permanentemente
        $trip->delete();

        return response()->noContent();
    }
}