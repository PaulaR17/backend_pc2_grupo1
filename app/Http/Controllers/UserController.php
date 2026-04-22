<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(User $user)
    {
        return response()->json($user->load(['profile', 'vehicles']));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'mail' => 'sometimes|email|max:100',
            'rol' => 'sometimes|in:USER,ADMIN',
            'status' => 'sometimes|boolean',
        ]);

        $user->update($data);
        return response()->json($user);
    }

    public function deactivate(User $user)
    {
        $user->update(['status' => false]);
        return response()->json(['ok' => true, 'deactivated' => true]);
    }

    public function stats(User $user)
    {
        return response()->json([
            'user_id' => $user->id,
            'history_count' => $user->history()->count(), 
            'favorites_count' => $user->favorites()->count(),
            'vehicles_count' => $user->vehicles()->count(),
        ]);
    }
}