<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PredictionController extends Controller
{
    public function index(Request $request)
    {
        return response()->json(['ok' => true, 'endpoint' => 'predictions.index', 'query' => $request->query()]);
    }

    public function latest()
    {
        return response()->json(['ok' => true, 'endpoint' => 'predictions.latest']);
    }

    public function byDistrict(string $district)
    {
        $districtCode = (int) $district; // 01 y 1 => 1 por si acas
        return response()->json(['ok' => true, 'endpoint' => 'predictions.byDistrict', 'district' => $district]);
    }
}