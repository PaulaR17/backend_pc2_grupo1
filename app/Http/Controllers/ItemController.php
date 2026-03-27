<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Item;
use App\Models\Inventory;
use App\Models\Equipment;
use App\Models\Badge;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    public function catalog()
    {
        return response()->json(Item::all());
    }

    public function inventory(int $userId)
    {
        User::findOrFail($userId);

        $rows = Inventory::where('user_id', $userId)->get();
        return response()->json($rows);
    }

    public function badges(int $userId)
    {
        User::findOrFail($userId);

        $rows = Badge::where('user_id', $userId)->get();
        return response()->json($rows);
    }

    public function updateEquipment(Request $request, int $userId)
    {
        User::findOrFail($userId);

        $data = $request->validate([
            'slot' => 'required|string|max:30',
            'item_id' => 'nullable|integer|exists:items,id,deleted_at,NULL',
        ]);

        //1 registro por slot solo, si ya hay uno con ese slot se actualiza, sino se crea nuevo
        $row = Equipment::updateOrCreate(
            ['user_id' => $userId, 'slot' => $data['slot']],
            ['item_id' => $data['item_id'], 'updated_at' => now()]
        );

        return response()->json($row);
    }
}
