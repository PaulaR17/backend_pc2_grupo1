<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    //devuelve el perfil del usuario o una plantilla vacia si no existe
    public function show(User $user)
    {
        $this->authorizeUserAccess($user);

        $profile = UserProfile::where('user_id', $user->id)->first();

        $resultado = $profile ?? [
            'user_id' => $user->id,
            'home_lat' => null,
            'home_lon' => null,
            'work_lat' => null,
            'work_lon' => null,
        ];

        return response()->json($resultado);
    }

    //guarda la direccion de casa del usuario
    public function setHome(Request $request, User $user)
    {
        $this->authorizeUserAccess($user);

        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
        ]);

        $profile = UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            ['home_lat' => $data['lat'], 'home_lon' => $data['lon']]
        );

        return response()->json($profile);
    }

    //guarda la direccion del trabajo
    public function setWork(Request $request, User $user)
    {
        $this->authorizeUserAccess($user);

        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
        ]);

        $profile = UserProfile::updateOrCreate(
            ['user_id' => $user->id],
            ['work_lat' => $data['lat'], 'work_lon' => $data['lon']]
        );

        return response()->json($profile);
    }

    //borra la direccion de casa
    public function clearHome(User $user)
    {
        $this->authorizeUserAccess($user);

        $profile = UserProfile::where('user_id', $user->id)->first();

        if ($profile) {
            $profile->home_lat = null;
            $profile->home_lon = null;
            $profile->save();
        }

        return response()->json(['ok' => true]);
    }

    //borra la direccion del trabajo
    public function clearWork(User $user)
    {
        $this->authorizeUserAccess($user);

        $profile = UserProfile::where('user_id', $user->id)->first();

        if ($profile) {
            $profile->work_lat = null;
            $profile->work_lon = null;
            $profile->save();
        }

        return response()->json(['ok' => true]);
    }

    //solo deja pasar al ADMIN o al dueño del perfil
    private function authorizeUserAccess(User $user): void
    {
        $authenticatedUser = auth()->user();

        if (!$authenticatedUser) {
            abort(401, 'Usuario no autenticado.');
        } else if ($authenticatedUser->rol !== 'ADMIN'
            && (int) $authenticatedUser->id !== (int) $user->id) {
            abort(403, 'No tienes permiso para acceder a este perfil.');
        }
    }
}
