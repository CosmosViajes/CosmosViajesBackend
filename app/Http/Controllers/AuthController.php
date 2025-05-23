<?php

// Esto dice en qué carpeta está este archivo
namespace App\Http\Controllers;

// Aquí decimos qué cosas vamos a usar en este archivo
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tymon\JWTAuth\Facades\JWTAuth;

// Esta clase se encarga de todo lo relacionado con el registro, el inicio y el cierre de sesión de los usuarios
class AuthController extends Controller
{
    // REGISTRO: sirve para crear un usuario nuevo
    public function register(Request $request)
    {
        // Aquí comprobamos que los datos que manda el usuario están bien
        $validated = $request->validate([
            'name' => 'required|string|max:255', // El nombre es obligatorio
            'email' => 'required|string|email|unique:users', // El correo es obligatorio y no puede estar repetido
            'password' => 'required|string|min:8|confirmed', // La contraseña debe tener mínimo 8 letras y estar confirmada
            'role' => 'sometimes|in:user,company,provider' // El rol es opcional, si no se pone es "user"
        ]);

        // Creamos el usuario y guardamos los datos en la base de datos
        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']), // Guardamos la contraseña de forma segura
            'role' => $validated['role'] ?? 'user'
        ]);

        // Devolvemos el usuario que se acaba de crear
        return response()->json([
            'user' => $user
        ], 201);
    }
    

    // LOGIN: sirve para iniciar sesión
    public function login(Request $request) {
        // Cogemos el correo y la contraseña que pone el usuario
        $credentials = $request->only('email', 'password');

        // Intentamos iniciar sesión con esos datos
        if (!$token = JWTAuth::attempt($credentials)) {
            // Si los datos están mal, devolvemos error
            return response()->json(['error' => 'Credenciales inválidas'], 401);
        }

        // Si todo va bien, buscamos los datos del usuario
        $user = auth()->user();

        // Devolvemos el token (como una llave para entrar) y los datos del usuario
        return response()->json([
            'token' => $token,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'photo' => $user->photo,
                'role' => $user->role,
            ]
        ]);
    }

    // LOGOUT: sirve para cerrar sesión
    public function logout(Request $request)
    {
        try {
            // Invalidamos el token, es decir, lo anulamos para que ya no sirva
            JWTAuth::invalidate(JWTAuth::parseToken());
            return response()->json([
                'success' => true,
                'message' => 'Sesión cerrada correctamente'
            ]);
        } catch (\Exception $e) {
            // Si algo sale mal, avisamos del error
            return response()->json([
                'success' => false,
                'error' => 'Error al cerrar sesión'
            ], 500);
        }
    }

    // Esta función sirve para saber el id del usuario que está conectado
    public function getUserId(Request $request) {
        $user = auth()->user();
        if (!$user) {
            // Si no hay usuario conectado, devolvemos error
            return response()->json(['error' => 'Usuario no autenticado'], 401);
        }
    
        // Si hay usuario, devolvemos su id
        return response()->json(['user_id' => $user->id], 200);
    }    
}