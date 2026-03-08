<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function show(int $userId)
    {
        User::findOrFail($userId);
        $profile = UserProfile::where('user_id', $userId)->first();
        return response()->json($profile ?? [
            'user_id' => $userId,
            'home_lat' => null,
            'home_lon' => null,
            'work_lat' => null,
            'work_lon' => null,
        ]);
    }

    public function setHome(Request $request, int $userId)
    {
        User::findOrFail($userId);
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
        ]);
        $profile = UserProfile::updateOrCreate(
            ['user_id' => $userId],
            ['home_lat' => $data['lat'], 'home_lon' => $data['lon']]
        );
        return response()->json($profile);
    }

    public function setWork(Request $request, int $userId)
    {
        User::findOrFail($userId);
        $data = $request->validate([
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
        ]);
        $profile = UserProfile::updateOrCreate(
            ['user_id' => $userId],
            ['work_lat' => $data['lat'], 'work_lon' => $data['lon']]
        );
        return response()->json($profile);
    }

    public function clearHome(int $userId)
    {
        User::findOrFail($userId);
        $profile = UserProfile::where('user_id', $userId)->first();
        if ($profile) {
            $profile->home_lat = null;
            $profile->home_lon = null;
            $profile->save();
        }
        return response()->json(['ok' => true]);
    }

    public function clearWork(int $userId)
    {
        User::findOrFail($userId);
        $profile = UserProfile::where('user_id', $userId)->first();
        if ($profile) {
            $profile->work_lat = null;
            $profile->work_lon = null;
            $profile->save();
        }
        return response()->json(['ok' => true]);
    }
}