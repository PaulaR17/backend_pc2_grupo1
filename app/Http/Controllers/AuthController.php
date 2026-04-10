<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function login(Request $request)
{
    $credentials = $request->only('email', 'password');
    $token = auth()->attempt($credentials);
    
    $status = 401;
    $data = ['error' => 'Credenciales invalidas'];

    if ($token) {
        $status = 200;
        $data = [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ];
    }

    return response()->json($data, $status);
}
}
