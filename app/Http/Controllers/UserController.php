<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(int $userId)
    {
        $user = User::with(['profile', 'vehicles'])->findOrFail($userId); //carga el perfil y los vehículos del usuario findOrFail para lanzar un 404 si no existe
        return response()->json($user);
    }

    public function update(Request $request, int $userId) //actualiza el usuario con los datos proporcionados en la solicitud
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:100', //sometimes para que no sea obligatorio, string para que sea una cadena de texto y max:100 para limitar a 100 caracteres
            'mail' => 'sometimes|email|max:100',
            'rol' => 'sometimes|in:USER,ADMIN',
            'status' => 'sometimes|boolean',
        ]);

        $user = User::findOrFail($userId);
        $user->fill($data);
        $user->save();
        return response()->json($user);
    }

    public function deactivate(int $userId)
    {
        $user = User::findOrFail($userId);
        $user->status = false;
        $user->save();
        return response()->json(['ok' => true, 'deactivated' => true]);
    }

    public function stats(int $userId) //estadisticas basicas, podemos meter mas
    {
        $user = User::findOrFail($userId);
        $historyCount = $user->history()->count(); 
        $favoritesCount = $user->favorites()->count();
        $vehiclesCount = $user->vehicles()->count();
        return response()->json([
            'user_id' => $user->id,
            'history_count' => $historyCount,
            'favorites_count' => $favoritesCount,
            'vehicles_count' => $vehiclesCount,
        ]);
    }
}