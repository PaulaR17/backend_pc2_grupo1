<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleLabel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VehicleController extends Controller
{
   
    public function labels()
    {
        return response()->json(
            VehicleLabel::all()
        );
    }

  
    public function index(int $userId)
    {
        User::findOrFail($userId);

        $vehicles = Vehicle::with('label')
            ->where('user_id', $userId)
            ->get();

        return response()->json($vehicles);
    }

    public function store(Request $request, int $userId)
    {
        User::findOrFail($userId);

        $data = $request->validate([
            'type' => 'required|in:CAR,MOTORBIKE,VAN',
            'vehicle_label_id' => 'nullable|integer|exists:vehicle_labels,id,deleted_at,NULL',
            'nickname' => 'nullable|string|max:50',
            'is_electric' => 'sometimes|boolean',
            'is_default' => 'sometimes|boolean',
        ]);

        $vehicle = new Vehicle();
        $vehicle->user_id = $userId;
        $vehicle->type = $data['type'];
        $vehicle->vehicle_label_id = $data['vehicle_label_id'] ?? null;
        $vehicle->nickname = $data['nickname'] ?? null;
        $vehicle->is_electric = (bool)($data['is_electric'] ?? false);
        $vehicle->is_default = (bool)($data['is_default'] ?? false);
        $vehicle->save();

        if ($vehicle->is_default) {
            Vehicle::where('user_id', $userId)
                ->where('id', '!=', $vehicle->id)
                ->update(['is_default' => false]);
        }

        return response()->json(
            Vehicle::with('label')->findOrFail($vehicle->id),
            201
        );
    }

 
    public function show(int $userId, int $vehicleId)
    {
        User::findOrFail($userId);

        $vehicle = Vehicle::with('label')
            ->where('user_id', $userId)
            ->where('id', $vehicleId)
            ->firstOrFail();

        return response()->json($vehicle);
    }

  
    public function update(Request $request, int $userId, int $vehicleId)
    {
        User::findOrFail($userId);

        $data = $request->validate([
            'type' => 'sometimes|in:CAR,MOTORBIKE,VAN',
            'vehicle_label_id' => 'sometimes|nullable|integer|exists:vehicle_labels,id,deleted_at,NULL',
            'nickname' => 'sometimes|nullable|string|max:50',
            'is_electric' => 'sometimes|boolean',
            'is_default' => 'sometimes|boolean',
        ]);

        $vehicle = Vehicle::where('user_id', $userId)
            ->where('id', $vehicleId)
            ->firstOrFail();

        $vehicle->fill($data);
        $vehicle->save();

        if (array_key_exists('is_default', $data) && $vehicle->is_default) {
            Vehicle::where('user_id', $userId)
                ->where('id', '!=', $vehicle->id)
                ->update(['is_default' => false]);
        }

        return response()->json(
            Vehicle::with('label')->findOrFail($vehicle->id)
        );
    }

   
    public function delete(int $userId, int $vehicleId)
    {
        User::findOrFail($userId);

        $vehicle = Vehicle::where('user_id', $userId)
            ->where('id', $vehicleId)
            ->firstOrFail();

        $vehicle->delete();

        return response()->json([
            'ok' => true,
            'message' => 'Vehicle deleted',
        ]);
    }


    public function setDefault(int $userId, int $vehicleId)
    {
        User::findOrFail($userId);

        $vehicle = Vehicle::where('user_id', $userId)
            ->where('id', $vehicleId)
            ->firstOrFail();

        DB::transaction(function () use ($userId, $vehicle) {
            Vehicle::where('user_id', $userId)->update([
                'is_default' => false,
            ]);

            $vehicle->update([
                'is_default' => true,
            ]);
        });

        return response()->json([
            'ok' => true,
            'default_vehicle_id' => $vehicleId,
        ]);
    }
}
