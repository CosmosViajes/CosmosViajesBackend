<?php

// Esto dice en qué carpeta está el archivo
namespace App\Http\Controllers;

// Aquí decimos que vamos a usar el modelo de usuario
use App\Models\User;

// Esta clase se encarga de mostrar los proveedores (empresas que ofrecen viajes)
class ProviderController extends Controller
{
    // Esta función sirve para sacar la lista de proveedores
    public function index()
    {
        // Buscamos todos los usuarios que son empresa y proveedor al mismo tiempo
        // También contamos cuántos viajes espaciales tiene cada uno
        $providers = User::where('is_company', true)
            ->where('is_provider', true)
            ->withCount('spaceTrips')
            ->get();

        // Devolvemos la lista de proveedores en formato JSON (como una lista que entiende la web)
        return response()->json($providers);
    }
}