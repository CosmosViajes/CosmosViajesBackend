<?php

namespace App\Http\Controllers\API;

use App\Models\UserRequest; // Cambiar el import
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class RequestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'type' => 'required|in:company,provider,other',
            'description' => 'nullable|string|max:500'
        ]);

        $userRequest = new UserRequest(); // Nombre actualizado
        $userRequest->user_id = $validated['user_id'];
        $userRequest->type = $validated['type'];
        $userRequest->description = $validated['description'];
        $userRequest->status = 'pending'; // Valor por defecto
        $userRequest->save();

        return response()->json($userRequest, 201);
    }

    public function index()
    {
        return UserRequest::with('user:id,name,email') // Cambiar aquí
            ->orderBy('created_at', 'desc')
            ->get();
    }

    public function updateStatus(Request $request, $id)
    {
        $requestModel = UserRequest::findOrFail($id); // Cambiar aquí
        
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected'
        ]);

        $requestModel->update(['status' => $validated['status']]);
        
        if($validated['status'] === 'approved') {
            $user = $requestModel->user;
            $user->update(['role' => $requestModel->type]);
        }

        return response()->json($requestModel);
    }
}