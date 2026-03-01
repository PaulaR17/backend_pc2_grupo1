<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function catalog(Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'items.catalog', 'query' => $request->query()]);
    }

    public function inventory(int $userId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.inventory', 'userId' => $userId]);
    }

    public function badges(int $userId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.badges', 'userId' => $userId]);
    }

    public function updateEquipment(int $userId, Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.equipment.update', 'userId' => $userId, 'payload' => $request->all()]);
    }
}