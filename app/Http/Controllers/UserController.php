<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Http;

// Esta clase se encarga de todo lo que tiene que ver con los usuarios en la web
class UserController extends Controller
{
    // Esta función busca el ID de un usuario a partir de su correo electrónico
    public function getUserIdByEmail(Request $request)
    {
        // Comprobamos que el correo esté bien escrito
        $validatedData = $request->validate([
            'email' => 'required|email',
        ]);

        // Buscamos al usuario con ese correo
        $user = User::where('email', $validatedData['email'])->first();

        // Si no existe, avisamos que no se encontró
        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado byEmail'], 404);
        }

        // Si existe, devolvemos su ID
        return response()->json(['user_id' => $user->id], 200);
    }

    // Esta función sirve para actualizar los datos de un usuario
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $rules = [];
        // Si quiere cambiar el nombre, comprobamos que esté bien
        if ($request->has('name')) {
            $rules['name'] = 'required|string|max:255';
        }
        // Si quiere cambiar el correo, comprobamos que esté bien y que no esté repetido
        if ($request->has('email')) {
            $rules['email'] = 'required|email|unique:users,email,'.$user->id;
        }
        // Si sube una foto nueva, comprobamos que sea una imagen y que no sea muy grande
        if ($request->hasFile('photo')) {
            $rules['photo'] = 'required|file|image|max:2048';
        }

        $validatedData = $request->validate($rules);

        // Si hay nombre nuevo, lo guardamos
        if ($request->has('name')) {
            $user->name = $validatedData['name'];
        }
        // Si hay correo nuevo, lo guardamos
        if ($request->has('email')) {
            $user->email = $validatedData['email'];
        }

        // Si hay foto nueva, la subimos a ImgBB y guardamos el enlace
        if ($request->hasFile('photo')) {
            $response = Http::asMultipart()->post('https://api.imgbb.com/1/upload', [
                'key' => env('IMGBB_API_KEY'),
                'image' => base64_encode(file_get_contents($request->file('photo')->getRealPath())),
            ]);

            if ($response->successful()) {
                $imageUrl = $response->json('data.url');
                $user->photo = $imageUrl;
            } else {
                // Si falla la subida, avisamos
                return response()->json(['error' => 'Error subiendo imagen a ImgBB'], 500);
            }
        }

        $user->save(); // Guardamos todos los cambios

        // Devolvemos el usuario actualizado y un mensaje de éxito
        return response()->json([
            'user' => $user,
            'message' => 'Actualización exitosa'
        ]);
    }

    // Esta función devuelve los datos de un usuario por su ID
    public function getUserData($userId)
    {
        $user = User::where('id', $userId)->first();

        // Si no existe, avisamos
        if (!$user) {
            return response()->json([
                'message' => 'Usuario no encontrado data',
            ], 404);
        }

        // Si existe, devolvemos sus datos
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

    // Esta función saca la lista de todos los proveedores (usuarios que ofrecen viajes)
    public function getProviders()
    {
        $providers = User::where('is_provider', true)
                    ->select('id', 'name')
                    ->get();

        return response()->json($providers);
    }

    // Esta función saca los viajes de un usuario si es proveedor y empresa
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

    // Esta función sirve para cambiar el rol (tipo) de usuario
    public function updateRole(User $user, Request $request)
    {
        $request->validate(['role' => 'required|in:user,company,provider']);
        $user->update(['role' => $request->role]);
        return response()->json($user);
    }
}