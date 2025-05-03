<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Storage;

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
            // Eliminar imagen anterior si existe
            if ($user->photo) {
                $oldPath = str_replace(asset('storage/'), '', $user->photo);
                Storage::disk('public')->delete($oldPath);
            }
    
            // Guardar nueva imagen
            $path = $request->file('photo')->store('profiles', 'public');
            $user->photo = asset("storage/$path");
        }

        $user->save();

        return response()->json([
            'user' => $user,
            'message' => 'Actualización exitosa'
        ]);
    }

    /**
     * Recuperar y mostrar la foto desde la base de datos.
     */
    public function getPhoto($userId)
    {
        $user = User::where('id', $userId)->first();

        if (!$user || !$user->photo) {
            return response()->json(['message' => 'Foto no encontrada'], 404);
        }

        // Retornar la imagen con el tipo MIME correcto
        return response($user->photo)->header('Content-Type', 'image/jpeg');
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