<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Http\Request;

class PetController extends Controller
{
    // Devuelve la mascota del usuario.
    // Si no existe, crea una con valores por defecto
    // para que el frontend nunca reciba un 404 al entrar
    // por primera vez en la página "Mi mascota".
    public function show(int $userId)
    {
        User::findOrFail($userId);

        $pet = Pet::where('user_id', $userId)->first();

        if (!$pet) {
            $pet = new Pet();
            $pet->user_id = $userId;
            $pet->name = 'Eco';
            $pet->level = 1;
            $pet->xp = 0;
            $pet->image = null;
            $pet->save();
        }

        return response()->json($pet);
    }

    // Actualiza los datos de la mascota del usuario.
    // Si por alguna razón no existe aún, la crea.
    public function update(Request $request, int $userId)
    {
        User::findOrFail($userId);

        $data = $request->validate([
            'name'  => 'sometimes|string|max:20',
            'level' => 'sometimes|integer|min:1',
            'xp'    => 'sometimes|integer|min:0',
            'image' => 'sometimes|nullable|string|max:255',
        ]);

        $pet = Pet::where('user_id', $userId)->first();

        if (!$pet) {
            $pet = new Pet();
            $pet->user_id = $userId;
        }

        $pet->fill($data);
        $pet->save();

        return response()->json($pet);
    }
}
