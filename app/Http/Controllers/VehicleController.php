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
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->get();

        return response()->json($vehicles);
    }

    public function store(Request $request, int $userId)
    {
        User::findOrFail($userId);

        $data = $request->validate([
            'type' => 'sometimes|in:CAR,MOTORBIKE,VAN',
            'vehicle_label_id' => 'nullable|integer|exists:vehicle_labels,id,deleted_at,NULL',

            'nickname' => 'nullable|string|max:50',
            'brand' => 'required|string|max:80',
            'model' => 'required|string|max:80',
            'year' => 'nullable|integer|min:1990|max:2035',
            'plate' => 'nullable|string|max:20',
            'fuel_type' => 'required|in:electric,hybrid,gasoline,diesel',
            'color_hex' => 'nullable|string|max:20',
            'color_name' => 'nullable|string|max:80',

            'is_electric' => 'sometimes|boolean',
            'is_default' => 'sometimes|boolean',
        ]);

        $vehicle = Vehicle::create([
            'user_id' => $userId,
            'type' => $data['type'] ?? 'CAR',
            'vehicle_label_id' => $data['vehicle_label_id'] ?? null,

            'nickname' => $data['nickname'] ?? $this->buildNickname($data),
            'brand' => $data['brand'],
            'model' => $data['model'],
            'year' => $data['year'] ?? null,
            'plate' => $data['plate'] ?? null,
            'fuel_type' => $data['fuel_type'],
            'color_hex' => $data['color_hex'] ?? '#1a1a2e',
            'color_name' => $data['color_name'] ?? 'Negro noche',

            'is_electric' => $data['is_electric'] ?? $this->isElectricFuel($data['fuel_type']),
            'is_default' => $data['is_default'] ?? false,
        ]);

        if ($vehicle->is_default) {
            $this->clearOtherDefaults($userId, $vehicle->id);
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

        $vehicle = Vehicle::where('user_id', $userId)
            ->where('id', $vehicleId)
            ->firstOrFail();

        $data = $request->validate([
            'type' => 'sometimes|in:CAR,MOTORBIKE,VAN',
            'vehicle_label_id' => 'sometimes|nullable|integer|exists:vehicle_labels,id,deleted_at,NULL',

            'nickname' => 'sometimes|nullable|string|max:50',
            'brand' => 'sometimes|string|max:80',
            'model' => 'sometimes|string|max:80',
            'year' => 'sometimes|nullable|integer|min:1990|max:2035',
            'plate' => 'sometimes|nullable|string|max:20',
            'fuel_type' => 'sometimes|in:electric,hybrid,gasoline,diesel',
            'color_hex' => 'sometimes|nullable|string|max:20',
            'color_name' => 'sometimes|nullable|string|max:80',

            'is_electric' => 'sometimes|boolean',
            'is_default' => 'sometimes|boolean',
        ]);

        if (array_key_exists('fuel_type', $data) && !array_key_exists('is_electric', $data)) {
            $data['is_electric'] = $this->isElectricFuel($data['fuel_type']);
        }

        if (
            (!array_key_exists('nickname', $data) || !$data['nickname'])
            && (array_key_exists('brand', $data) || array_key_exists('model', $data))
        ) {
            $merged = array_merge($vehicle->toArray(), $data);
            $data['nickname'] = $this->buildNickname($merged);
        }

        $vehicle->fill($data);
        $vehicle->save();

        if (array_key_exists('is_default', $data) && $vehicle->is_default) {
            $this->clearOtherDefaults($userId, $vehicle->id);
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

    private function clearOtherDefaults(int $userId, int $currentVehicleId): void
    {
        Vehicle::where('user_id', $userId)
            ->where('id', '!=', $currentVehicleId)
            ->update(['is_default' => false]);
    }

    private function isElectricFuel(string $fuelType): bool
    {
        return in_array($fuelType, ['electric', 'hybrid'], true);
    }

    private function buildNickname(array $data): string
    {
        $brand = $data['brand'] ?? '';
        $model = $data['model'] ?? '';

        return trim($brand . ' ' . $model) ?: 'Vehículo';
    }
}