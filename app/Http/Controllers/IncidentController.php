<?php

namespace App\Http\Controllers;

use App\Models\Incident;

class IncidentController extends Controller
{
    public function index()
    {
        $rows = Incident::where('active', true)
            ->orderByDesc('created_at')
            ->get();

        return response()->json($rows);
    }

    public function show(int $incidentId)
    {
        $row = Incident::findOrFail($incidentId);
        return response()->json($row);
    }
}