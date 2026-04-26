<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(User $user)
    {
        $this->authorizeUserAccess($user);

        return response()->json(
            $user->load(['profile', 'vehicles'])
        );
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeUserAccess($user);

        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'mail' => 'sometimes|email|max:100|unique:users,mail,' . $user->id,
        ]);

        $user->update($data);

        return response()->json(
            $user->fresh()->load(['profile', 'vehicles'])
        );
    }

    public function deactivate(User $user)
    {
        $this->authorizeUserAccess($user);

        $user->update(['status' => false]);

        return response()->json([
            'ok' => true,
            'deactivated' => true,
        ]);
    }

    public function stats(User $user)
    {
        $this->authorizeUserAccess($user);

        return response()->json([
            'user_id' => $user->id,
            'history_count' => $user->history()->count(),
            'favorites_count' => $user->favorites()->count(),
            'vehicles_count' => $user->vehicles()->count(),
        ]);
    }

    private function authorizeUserAccess(User $user): void
    {
        $authenticatedUser = auth()->user();

        if (!$authenticatedUser) {
            abort(401, 'Usuario no autenticado.');
        }

        if ($authenticatedUser->rol === 'ADMIN') {
            return;
        }

        if ((int) $authenticatedUser->id !== (int) $user->id) {
            abort(403, 'No tienes permiso para acceder a este usuario.');
        }
    }
}