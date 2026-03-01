<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class VehicleController extends Controller
{
    public function index(int $userId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.vehicles.index', 'userId' => $userId]);
    }

    public function store(int $userId, Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.vehicles.store', 'userId' => $userId, 'payload' => $request->all()]);
    }

    public function show(int $userId, int $vehicleId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.vehicles.show', 'userId' => $userId, 'vehicleId' => $vehicleId]);
    }

    public function update(int $userId, int $vehicleId, Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.vehicles.update', 'userId' => $userId, 'vehicleId' => $vehicleId, 'payload' => $request->all()]);
    }

    public function delete(int $userId, int $vehicleId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.vehicles.delete', 'userId' => $userId, 'vehicleId' => $vehicleId]);
    }

    public function setDefault(int $userId, int $vehicleId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'users.vehicles.setDefault', 'userId' => $userId, 'vehicleId' => $vehicleId]);
    }

    public function labels()
    {
        return response()->json(['ok' => true, 'endpoint' => 'vehicle-labels.index']);
    }
}