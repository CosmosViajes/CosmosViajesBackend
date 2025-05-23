<?php

// Esto dice en qué carpeta está el archivo
namespace App\Http\Controllers\API;

// Aquí decimos qué cosas vamos a usar en el archivo
use App\Models\UserRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

// Esta clase se encarga de gestionar las "peticiones" o "solicitudes" que hacen los usuarios
class RequestController extends Controller
{
    // Esta función sirve para guardar una nueva solicitud que manda un usuario
    public function store(Request $request)
    {
        // Aquí comprobamos que los datos que manda el usuario están bien
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id', // El usuario tiene que existir
            'type' => 'required|in:company,provider,other', // El tipo solo puede ser empresa, proveedor u otro
            'description' => 'nullable|string|max:500' // La descripción es opcional y no puede ser muy larga
        ]);

        // Creamos la solicitud y rellenamos los datos
        $userRequest = new UserRequest();
        $userRequest->user_id = $validated['user_id'];
        $userRequest->type = $validated['type'];
        $userRequest->description = $validated['description'];
        $userRequest->status = 'pending'; // Al principio siempre está "pendiente"
        $userRequest->save(); // Guardamos la solicitud en la base de datos

        // Devolvemos la solicitud guardada
        return response()->json($userRequest, 201);
    }

    // Esta función sirve para ver todas las solicitudes que han hecho los usuarios
    public function index()
    {
        // Buscamos todas las solicitudes, junto con el nombre y correo del usuario que la hizo
        // Las ordenamos para que salgan primero las más nuevas
        return UserRequest::with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // Esta función sirve para cambiar el estado de una solicitud (por ejemplo, aprobarla o rechazarla)
    public function updateStatus(Request $request, $id)
    {
        // Buscamos la solicitud por su id
        $requestModel = UserRequest::findOrFail($id);
        
        // Comprobamos que el estado que queremos poner es correcto (solo puede ser aprobado o rechazado)
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        // Cambiamos el estado de la solicitud
        $requestModel->update(['status' => $validated['status']]);
        
        // Si la solicitud se aprueba, cambiamos el tipo de usuario (por ejemplo, de normal a empresa o proveedor)
        if($validated['status'] === 'approved') {
            $user = $requestModel->user;
            $user->update(['role' => $requestModel->type]);
        }

        // Devolvemos la solicitud actualizada
        return response()->json($requestModel);
    }
}