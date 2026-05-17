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
    //devuelve todos los items de la tienda
    public function catalog()
    {
        $items = Item::all();

        return response()->json($items);
    }

    //items que tiene el usuario en su inventario
    public function inventory(int $userId)
    {
        User::findOrFail($userId);

        $rows = Inventory::where('user_id', $userId)->get();

        return response()->json($rows);
    }

    //insignias del usuario
    public function badges(int $userId)
    {
        User::findOrFail($userId);

        $rows = Badge::where('user_id', $userId)->get();

        return response()->json($rows);
    }

    //devuelve los slots equipados del usuario (solo los que tienen item)
    public function equipment(int $userId)
    {
        User::findOrFail($userId);

        $rows = Equipment::where('user_id', $userId)
            ->whereNotNull('item_id')
            ->get();

        return response()->json($rows);
    }

    //equipa o desequipa un item en un slot
    public function updateEquipment(Request $request, int $userId)
    {
        User::findOrFail($userId);

        $data = $request->validate([
            'slot' => 'required|string|max:30',
            'item_id' => 'nullable|integer|exists:items,id,deleted_at,NULL',
        ]);

        $row = Equipment::updateOrCreate(
            ['user_id' => $userId, 'slot' => $data['slot']],
            ['item_id' => $data['item_id'], 'updated_at' => now()]
        );

        return response()->json($row);
    }
}
