<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RouteController extends Controller
{
    public function calculate(Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'routes.calculate', 'payload' => $request->all()]);
    }

    public function preview(Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'routes.preview', 'payload' => $request->all()]);
    }

    public function detail(int $historyId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'routes.detail', 'historyId' => $historyId]);
    }

    public function history(int $userId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.routes.history', 'userId' => $userId]);
    }

    public function deleteHistory(int $userId, int $historyId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.routes.history.delete', 'userId' => $userId, 'historyId' => $historyId]);
    }

    public function favorites(int $userId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.routes.favorites', 'userId' => $userId]);
    }

    public function addFavorite(int $userId, Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.routes.favorites.add', 'userId' => $userId, 'payload' => $request->all()]);
    }

    public function removeFavorite(int $userId, int $favoriteId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.routes.favorites.remove', 'userId' => $userId, 'favoriteId' => $favoriteId]);
    }
}