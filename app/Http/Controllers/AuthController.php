<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'mail' => 'required|email|max:100|unique:users,mail',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'mail' => $data['mail'],
            'password_hash' => Hash::make($data['password']),
            'rol' => 'USER',
            'status' => true,
        ]);

        $token = auth()->login($user);

        return response()->json([
            'message' => 'Usuario registrado correctamente',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'mail' => $user->mail,
                'rol' => $user->rol,
                'status' => $user->status,
            ],
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'mail' => 'required|email',
            'password' => 'required|string',
        ]);

        $credentials = [
            'mail' => $data['mail'],
            'password' => $data['password'],
        ];

        $token = auth()->attempt($credentials);

        if (!$token) {
            throw ValidationException::withMessages([
                'mail' => ['Credenciales incorrectas.'],
            ]);
        }

        $user = auth()->user();

        if (!$user->status) {
            return response()->json([
                'error' => 'user_inactive',
                'message' => 'Este usuario está desactivado.',
            ], 403);
        }

        return response()->json([
            'message' => 'Login correcto',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'mail' => $user->mail,
                'rol' => $user->rol,
                'status' => $user->status,
            ],
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
        ]);
    }

    public function me()
    {
        $user = auth()->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'mail' => $user->mail,
            'rol' => $user->rol,
            'status' => $user->status,
        ]);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json([
            'message' => 'Sesión cerrada correctamente',
        ]);
    }
}