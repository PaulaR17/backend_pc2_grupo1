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
            'incidents_active' => Incident::where('active', true)->count(),
        ]);
    }

    public function users()
    {
        return response()->json(User::orderBy('id')->get());
    }

    public function userDetail(int $userId)
    {
        $user = User::with(['profile', 'vehicles'])->findOrFail($userId);
        return response()->json($user);
    }

    public function updateUser(Request $request, int $userId)
    {
        $data = $request->validate([
            'name' => 'sometimes|string|max:100',
            'mail' => 'sometimes|email|max:100',
            'rol' => 'sometimes|in:USER,ADMIN',
            'status' => 'sometimes|boolean',
        ]);

        $user = User::findOrFail($userId);
        $user->fill($data);
        $user->save();

        return response()->json($user);
    }

    public function deactivateUser(int $userId)
    {
        $user = User::findOrFail($userId);
        $user->status = false;
        $user->save();

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

        $incident = new Incident();
        $incident->fill($data);
        $incident->created_at = now();
        $incident->save();

        return response()->json($incident, 201);
    }

    public function updateIncident(Request $request, int $incidentId)
    {
        $data = $request->validate([
            'type' => 'sometimes|in:ACCIDENT,ROADWORK,EVENT',
            'lat' => 'sometimes|numeric|between:-90,90',
            'lon' => 'sometimes|numeric|between:-180,180',
            'active' => 'sometimes|boolean',
            'title' => 'sometimes|string|max:120',
            'description' => 'sometimes|string|max:1000',
        ]);

        $incident = Incident::findOrFail($incidentId);
        $incident->fill($data);
        $incident->save();

        return response()->json($incident);
    }

    public function deleteIncident(int $incidentId)
    {
        $incident = Incident::findOrFail($incidentId);
        $incident->active = false;
        $incident->save();

        return response()->json(['ok' => true, 'deleted' => true]);
    }

    public function runPredictions()
    {
        // TODO: aquí luego lanzas el job/command que calcule y guarde predicciones
        // De momento solo confirmamos que el endpoint existe.
        return response()->json(['ok' => true, 'message' => 'Prediction job trigger placeholder']);
    }
}