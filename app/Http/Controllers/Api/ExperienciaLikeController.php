<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExperienciaLike;
use App\Models\Experiencia;
use Illuminate\Http\Request;

class ExperienciaLikeController extends Controller
{
    public function toggle($id, $userId)
    {

        $like = ExperienciaLike::where('experiencia_id', $id)
                            ->where('user_id', $userId)
                            ->first();

        if ($like) {
            $like->delete();
            $liked = false;
        } else {
            ExperienciaLike::create([
                'experiencia_id' => $id,
                'user_id' => $userId
            ]);
            $liked = true;
        }

        $likesCount = ExperienciaLike::where('experiencia_id', $id)->count();
        Experiencia::where('id', $id)->update(['likes' => $likesCount]);

        return response()->json([
            'liked' => $liked,
            'likes' => $likesCount
        ]);
    }

    public function userLikes(Request $request)
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id'
        ]);

        $userId = $request->query('user_id');
        $likes = ExperienciaLike::where('user_id', $userId)
                    ->pluck('experiencia_id')
                    ->toArray();

        return response()->json($likes);
    }

}