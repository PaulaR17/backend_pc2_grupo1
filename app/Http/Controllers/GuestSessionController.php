<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GuestSessionController extends Controller
{
    public function create(Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'guest.sessions.create', 'payload' => $request->all()]);
    }

    public function quota(string $sessionId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'guest.sessions.quota', 'sessionId' => $sessionId]);
    }

    public function calculateRoute(string $sessionId, Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'guest.sessions.routes.calculate', 'sessionId' => $sessionId, 'payload' => $request->all()]);
    }
}