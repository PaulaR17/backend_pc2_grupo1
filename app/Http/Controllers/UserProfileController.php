<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function show(int $userId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.profile.show', 'userId' => $userId]);
    }

    public function setHome(int $userId, Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.locations.home.set', 'userId' => $userId, 'payload' => $request->all()]);
    }

    public function setWork(int $userId, Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.locations.work.set', 'userId' => $userId, 'payload' => $request->all()]);
    }

    public function clearHome(int $userId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.locations.home.clear', 'userId' => $userId]);
    }

    public function clearWork(int $userId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.locations.work.clear', 'userId' => $userId]);
    }
}