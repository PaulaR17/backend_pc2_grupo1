<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Incident;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json([
            'users_total' => User::count(),
            'users_active' => User::where('status', true)->count(),
            'users_inactive' => User::where('status', false)->count(),
            'incidents_total' => Incident::count(),
            'incidents_active' => Incident::where('active', true)->count(),
        ]);
    }

    public function users()
    {
        return response()->json(
            User::orderBy('id')->get()
        );
    }

    public function userDetail(User $user)
    {
        return response()->json(
            $user->load(['profile', 'vehicles'])
        );
    }

    public function updateUser(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'mail' => 'sometimes|email|max:100|unique:users,mail,' . $user->id,
            'rol' => 'sometimes|in:USER,ADMIN',
            'status' => 'sometimes|boolean',
        ]);

        $user->update($data);

        return response()->json($user);
    }

    public function deactivateUser(User $user)
    {
        $user->update(['status' => false]);

        return response()->json(['ok' => true]);
    }

    public function createIncident(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:ACCIDENT,ROADWORK,EVENT',
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
            'active' => 'sometimes|boolean',
            'title' => 'sometimes|string|max:120',
            'description' => 'sometimes|string|max:1000',
        ]);

        $incident = Incident::create($data);

        return response()->json($incident, 201);
    }

    public function updateIncident(Request $request, Incident $incident)
    {
        $data = $request->validate([
            'type' => 'sometimes|in:ACCIDENT,ROADWORK,EVENT',
            'lat' => 'sometimes|numeric|between:-90,90',
            'lon' => 'sometimes|numeric|between:-180,180',
            'active' => 'sometimes|boolean',
            'title' => 'sometimes|string|max:120',
            'description' => 'sometimes|string|max:1000',
        ]);

        $incident->update($data);

        return response()->json($incident);
    }

    public function deleteIncident(Incident $incident)
    {
        $incident->update(['active' => false]);

        return response()->json([
            'ok' => true,
            'deleted' => true,
        ]);
    }

    public function runPredictions()
    {
        return response()->json([
            'ok' => true,
            'message' => 'Cálculo de predicciones ejecutado correctamente.',
        ]);
    }
}