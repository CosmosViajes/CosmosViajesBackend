<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Kreait\Firebase\Factory;
use Kreait\Firebase\ServiceAccount;

class UserController extends Controller
{
    /**
     * Obtiene el ID del usuario por su correo electrónico.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserIdByEmail(Request $request)
    {
        // Validar que se envíe un correo electrónico
        $validatedData = $request->validate([
            'email' => 'required|email',
        ]);

        // Buscar al usuario por su correo electrónico
        $user = User::where('email', $validatedData['email'])->first();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado byEmail'], 404);
        }

        // Devolver el ID del usuario
        return response()->json(['user_id' => $user->id], 200);
    }

    public function update(Request $request, $id)
{
    $user = User::findOrFail($id);

    $rules = [];
    if ($request->has('name')) {
        $rules['name'] = 'required|string|max:255';
    }
    if ($request->has('email')) {
        $rules['email'] = 'required|email|unique:users,email,'.$user->id;
    }
    if ($request->hasFile('photo')) {
        $rules['photo'] = 'required|file|image|max:2048';
    }

    $validatedData = $request->validate($rules);

    if ($request->has('name')) {
        $user->name = $validatedData['name'];
    }
    if ($request->has('email')) {
        $user->email = $validatedData['email'];
    }

    if ($request->hasFile('photo')) {
        // Configurar Firebase
        $factory = (new Factory)->withServiceAccount(config('firebase.credentials.file'));
        $storage = $factory->createStorage();
        $bucket = $storage->getBucket();

        // Eliminar imagen anterior si existe
        if ($user->photo) {
            try {
                // Extraer el path de Firebase de la URL almacenada
                $oldPath = parse_url($user->photo, PHP_URL_PATH);
                $oldPath = ltrim($oldPath, '/'); // Quitar la barra inicial
                $bucket->object($oldPath)->delete();
            } catch (\Exception $e) {
                // Manejar error si la imagen no existe en Firebase
                \Log::error("Error eliminando imagen de Firebase: " . $e->getMessage());
            }
        }

        // Subir nueva imagen a Firebase
        try {
            $image = $request->file('photo');
            $firebasePath = 'profiles/' . uniqid() . '.' . $image->getClientOriginalExtension();
            
            $stream = fopen($image->getRealPath(), 'r');
            $bucket->upload($stream, ['name' => $firebasePath]);
            
            // Obtener URL pública
            $user->photo = 'https://storage.googleapis.com/' . $bucket->name() . '/' . $firebasePath;
            
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error subiendo la imagen: ' . $e->getMessage()
            ], 500);
        }
        }

        $user->save();

        return response()->json([
            'user' => $user,
            'message' => 'Actualización exitosa'
        ]);
    }

    public function getUserData($userId)
    {
        // Buscar al usuario por su ID
        $user = User::where('id', $userId)->first();

        // Verificar si el usuario existe
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado data',
            ], 404);
        }

        // Devolver los datos del usuario
        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'photo' => $user->photo,
                'is_company' => $user->is_company,
                'is_provider' => $user->is_provider,
                'role' => $user->role,
            ],
        ]);
    }

    public function getProviders()
    {
        $providers = User::where('is_provider', true)
                    ->select('id', 'name')
                    ->get();

        return response()->json($providers);
    }

    public function getUserFlights(User $user)
    {
        if (!$user->is_company || !$user->is_provider) {
            abort(403, 'El usuario no es proveedor');
        }

        return response()->json([
            'user' => $user,
            'flights' => $user->spaceTrips
        ]);
    }

    public function updateRole(User $user, Request $request)
    {
        $request->validate(['role' => 'required|in:user,company,provider']);
        $user->update(['role' => $request->role]);
        return response()->json($user);
    }
    
}