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
        return response()->json(['ok' => true, 'endpoint' => 'predictions.byDistrict', 'district' => $district]);
    }
}