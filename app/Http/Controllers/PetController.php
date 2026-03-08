<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Pet;
use Illuminate\Http\Request;

class PetController extends Controller
{
    public function show(int $userId)
    {
        User::findOrFail($userId);

        $pet = Pet::where('user_id', $userId)->first();
        return response()->json($pet);
    }

    public function update(Request $request, int $userId)
    {
        User::findOrFail($userId);

        $data = $request->validate([
            'name' => 'sometimes|string|max:50',
            'level' => 'sometimes|integer|min:1|max:999',
            'xp' => 'sometimes|integer|min:0|max:999999',
        ]);

        $pet = Pet::where('user_id', $userId)->first();
        if (!$pet) {
            $pet = new Pet();
            $pet->user_id = $userId;
        }

        $pet->fill($data);
        $pet->updated_at = now();
        $pet->save();

        return response()->json($pet);
    }
}