<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Item;
use App\Models\Transaction;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Http\Request;

//gestiona el "monedero" del usuario: balance y compras en la tienda
class WalletController extends Controller
{
    private RewardService $rewards;

    public function __construct(RewardService $rewards)
    {
        $this->rewards = $rewards;
    }

    //balance actual y ultimos movimientos (chapitas)
    public function balance(User $user)
    {
        $movimientos = Transaction::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return response()->json([
            'user_id' => $user->id,
            'balance' => $this->rewards->balance($user->id),
            'recent' => $movimientos,
        ]);
    }

    //compra un item de la tienda: descuenta el precio y lo añade al inventario
    public function purchase(Request $request, User $user)
    {
        $data = $request->validate([
            'item_id' => 'required|integer|exists:items,id,deleted_at,NULL',
        ]);

        $item = Item::findOrFail($data['item_id']);
        $balanceActual = $this->rewards->balance($user->id);
        $yaLoTiene = Inventory::where('user_id', $user->id)
            ->where('item_id', $item->id)
            ->exists();

        $respuesta = null;

        if ($yaLoTiene) {
            $respuesta = response()->json([
                'error' => 'already_owned',
                'message' => 'Ya tienes este item en tu inventario.',
            ], 409);
        } elseif ($balanceActual < (int) $item->price) {
            $respuesta = response()->json([
                'error' => 'insufficient_funds',
                'message' => 'No tienes suficientes chapitas para este item.',
                'balance' => $balanceActual,
                'price' => (int) $item->price,
            ], 402);
        } else {
            //la compra son dos pasos: registrar transaccion negativa + insertar inventario
            Transaction::create([
                'user_id' => $user->id,
                'type' => 'PURCHASE',
                'amount' => -1 * (int) $item->price,
            ]);

            $entrada = Inventory::create([
                'user_id' => $user->id,
                'item_id' => $item->id,
                'quantity' => 1,
            ]);

            $respuesta = response()->json([
                'ok' => true,
                'item' => $item,
                'inventory_id' => $entrada->id,
                'balance' => $this->rewards->balance($user->id),
            ], 201);
        }

        return $respuesta;
    }
}
