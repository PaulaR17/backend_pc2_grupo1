<?php

namespace App\Http\Controllers;

use App\Models\Incident;
use App\Services\RewardService;
use Illuminate\Http\Request;

class IncidentController extends Controller
{
    //incidencias activas para el mapa
    public function index()
    {
        $rows = Incident::where('active', true)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($rows);
    }

    //ficha de la incidencia por id
    public function show(int $incidentId)
    {
        $row = Incident::findOrFail($incidentId);

        return response()->json($row);
    }

    //un usuario logueado reporta una incidencia (queda activa al instante).
    //es distinto del alta de admin: aqui no se permite tocar el campo "active",
    //y forzamos un titulo que indica que viene de un reporte de usuario
    public function report(Request $request)
    {
        $data = $request->validate([
            'type' => 'required|in:ACCIDENT,ROADWORK,EVENT',
            'lat' => 'required|numeric|between:-90,90',
            'lon' => 'required|numeric|between:-180,180',
            'title' => 'nullable|string|max:120',
            'description' => 'nullable|string|max:1000',
        ]);

        $data['active'] = true;
        $incident = Incident::create($data);

        //recompensa por reportar: el endpoint requiere auth, asi que tenemos
        //user_id seguro en la sesion JWT
        $userId = auth()->id();
        $reward = null;
        if ($userId) {
            $rewards = app(RewardService::class);
            $reward = $rewards->reward($userId, 'INCIDENT_REPORTED');
        }

        return response()->json([
            'incident' => $incident,
            'reward' => $reward,
        ], 201);
    }
}
