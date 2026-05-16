<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    //datos del usuario con perfil y vehiculos
    public function show(User $user)
    {
        $this->authorizeUserAccess($user);

        $usuario = $user->load(['profile', 'vehicles']);

        return response()->json($usuario);
    }

    //edita nombre o email del usuario
    public function update(Request $request, User $user)
    {
        $this->authorizeUserAccess($user);

        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'mail' => 'sometimes|email|max:100|unique:users,mail,' . $user->id,
        ]);

        $user->update($data);

        $usuario = $user->fresh()->load(['profile', 'vehicles']);

        return response()->json($usuario);
    }

    //baja logica de la cuenta
    public function deactivate(User $user)
    {
        $this->authorizeUserAccess($user);

        $user->update(['status' => false]);

        return response()->json([
            'ok' => true,
            'deactivated' => true,
        ]);
    }

    //contadores rapidos del panel
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

    //solo deja pasar al ADMIN o al dueño de la cuenta
    private function authorizeUserAccess(User $user): void
    {
        $authenticatedUser = auth()->user();

        if (!$authenticatedUser) {
            abort(401, 'Usuario no autenticado.');
        } else if ($authenticatedUser->rol !== 'ADMIN'
            && (int) $authenticatedUser->id !== (int) $user->id) {
            abort(403, 'No tienes permiso para acceder a este usuario.');
        }
    }
}
