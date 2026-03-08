<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use Illuminate\Http\Request;

class PredictionController extends Controller
{
    public function index(Request $request)
    {
        $query = Prediction::query();

        if ($request->has('district')) {
            $query->where('district', (int)$request->query('district'));
        }

        if ($request->has('limit')) {
            $limit = min(max((int)$request->query('limit'), 1), 200);
            $query->limit($limit);
        }

        $rows = $query->orderByDesc('predicted_at')->get();

        return response()->json($rows);
    }

    public function latest()
    {
        $row = Prediction::orderByDesc('predicted_at')->first();

        return response()->json($row);
    }

    public function byDistrict(string $district)
    {
        $districtNum = (int)$district;

        $row = Prediction::where('district', $districtNum)
            ->orderByDesc('predicted_at')
            ->first();

        return response()->json($row);
    }
}