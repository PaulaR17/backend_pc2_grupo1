<?php

namespace App\Http\Controllers;

use App\Models\Pet;
use App\Models\User;
use Illuminate\Http\Request;

class PetController extends Controller
{
   
    public function show(int $userId)
    {
        User::findOrFail($userId);

        $pet = Pet::where('user_id', $userId)->first();

        if (!$pet) {
            return response()->json([
                'message' => 'Pet not found',
            ], 404);
        }

        return response()->json($pet);
    }

   
    public function update(Request $request, int $userId)
    {
        User::findOrFail($userId);

        $data = $request->validate([
            'name' => 'sometimes|string|max:20',
            'level' => 'sometimes|integer|min:1',
            'experience' => 'sometimes|integer|min:0',
            'image' => 'sometimes|nullable|string|max:255',
        ]);

        $pet = Pet::where('user_id', $userId)->first();

        if (!$pet) {
            $pet = new Pet();
            $pet->user_id = $userId;
            $pet->name = $data['name'] ?? 'Pet';
            $pet->level = $data['level'] ?? 1;
            $pet->experience = $data['experience'] ?? 0;
            $pet->image = $data['image'] ?? null;
            $pet->save();
        } else {
            $pet->fill($data);
            $pet->save();
        }

        return response()->json($pet);
    }
}