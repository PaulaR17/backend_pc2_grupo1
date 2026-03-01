<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class IncidentController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'incidents.index', 'query' => $request->query()]);
    }

    public function show(int $incidentId)
    {
        return response()->json(['ok' => true, 'endpoint' => 'incidents.show', 'incidentId' => $incidentId]);
    }
}