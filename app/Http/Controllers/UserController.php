<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserController extends Controller
{
    public function show(int $userId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.show', 'userId' => $userId]);
    }

    public function update(int $userId, Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.update', 'userId' => $userId, 'payload' => $request->all()]);
    }

    public function deactivate(int $userId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.deactivate', 'userId' => $userId]);
    }

    public function stats(int $userId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.stats', 'userId' => $userId]);
    }
}