<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PetController extends Controller
{
    public function show(int $userId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.pet.show', 'userId' => $userId]);
    }

    public function update(int $userId, Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.pet.update', 'userId' => $userId, 'payload' => $request->all()]);
    }
}