<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function show(User $user)
    {
        $this->authorizeUserAccess($user);

        $profile = UserProfile::where('user_id', $user->id)->first();

        return response()->json($profile ?? [
            'user_id' => $user->id,
            'home_lat' => null,
            'home_lon' => null,
            'work_lat' => null,
            'work_lon' => null,
        ]);
    }

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
            abort(403, 'No tienes permiso para acceder a este perfil.');
        }
    }
}