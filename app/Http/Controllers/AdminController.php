<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request; // importar la clase Request de Laravel para manejar las solicitudes HTTP

// Devolvemos una respuesta JSON porque estamos trabajando con una API REST. En esta fase inicial el endpoint aún no está conectado a la base de datos,
// así que devolvemos un JSON simple para comprobar que la ruta funciona

return response()->json(['ok' => true, 'endpoint' => 'admin.users.index']);
class AdminController extends Controller
{
    public function dashboard()
    {
        return response()->json(['ok' => true, 'endpoint' => 'admin.dashboard']);
    }

    public function users()
    {
        return response()->json(['ok' => true, 'endpoint' => 'admin.users.index']);
    }

    public function userDetail(int $userId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'admin.users.show', 'userId' => $userId]);
    }

    public function updateUser(int $userId, Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'admin.users.update', 'userId' => $userId, 'payload' => $request->all()]);
    }

    public function deactivateUser(int $userId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'admin.users.deactivate', 'userId' => $userId]);
    }

    public function createIncident(Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'admin.incidents.create', 'payload' => $request->all()]);
    }

    public function updateIncident(int $incidentId, Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'admin.incidents.update', 'incidentId' => $incidentId, 'payload' => $request->all()]);
    }

    public function deleteIncident(int $incidentId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'admin.incidents.delete', 'incidentId' => $incidentId]);
    }

    public function runPredictions(Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'admin.predictions.run', 'payload' => $request->all()]);
    }
}