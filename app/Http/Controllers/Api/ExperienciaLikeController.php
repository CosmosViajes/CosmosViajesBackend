<?php

// Esto dice en qué carpeta está este archivo
namespace App\Http\Controllers\Api;

// Aquí decimos qué cosas vamos a usar en este archivo
use App\Http\Controllers\Controller;
use App\Models\ExperienciaLike;
use App\Models\Experiencia;
use Illuminate\Http\Request;

// Esta clase se encarga de los "me gusta" (likes) en las experiencias
class ExperienciaLikeController extends Controller
{
    // Esta función sirve para poner o quitar un "me gusta" en una experiencia
    public function toggle($id, $userId)
    {
        // Buscamos si este usuario ya le ha dado "me gusta" a esta experiencia
        $like = ExperienciaLike::where('experiencia_id', $id)
                            ->where('user_id', $userId)
                            ->first();

        if ($like) {
            // Si ya había dado "me gusta", lo quitamos (borramos el like)
            $like->delete();
            $liked = false; // Ahora ya no le gusta
        } else {
            // Si no había dado "me gusta", lo ponemos (creamos el like)
            ExperienciaLike::create([
                'experiencia_id' => $id,
                'user_id' => $userId
            ]);
            $liked = true; // Ahora sí le gusta
        }

        // Contamos cuántos "me gusta" tiene la experiencia ahora
        $likesCount = ExperienciaLike::where('experiencia_id', $id)->count();
        // Actualizamos ese número en la experiencia
        Experiencia::where('id', $id)->update(['likes' => $likesCount]);

        // Devolvemos si al final le gusta o no, y cuántos "me gusta" hay en total
        return response()->json([
            'liked' => $liked,
            'likes' => $likesCount
        ]);
    }

    // Esta función sirve para saber a qué experiencias le ha dado "me gusta" un usuario
    public function userLikes(Request $request)
    {
        // Comprobamos que el usuario existe y que el dato está bien
        $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ]);

        // Cogemos el id del usuario de la petición
        $userId = $request->query('user_id');
        // Buscamos todas las experiencias a las que este usuario le ha dado "me gusta"
        $likes = ExperienciaLike::where('user_id', $userId)
                    ->pluck('experiencia_id')
                    ->toArray();

        // Devolvemos la lista de experiencias que le gustan a este usuario
        return response()->json($likes);
    }

}