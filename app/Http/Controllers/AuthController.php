<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = [
            'mail' => $request->input('mail'),
            'password' => $request->input('password'),
        ];

        $token = auth()->attempt($credentials);

        if (!$token) {
            return response()->json([
                'error' => 'Credenciales invalidas'
            ], 401);
        }

        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }
}