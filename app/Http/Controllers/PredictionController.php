<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use Illuminate\Http\Request;

//consultas sobre la tabla predictions que llena PC1
class PredictionController extends Controller
{
    //listado de predicciones con filtros opcionales
    public function index(Request $request)
    {
        $query = Prediction::query();

        if ($request->has('district')) {
            $query->where('district', (int) $request->query('district'));
        }

        if ($request->has('target_type')) {
            $query->where('target_type', (string) $request->query('target_type'));
        }

        if ($request->has('for_date')) {
            $query->where('for_date', (string) $request->query('for_date'));
        }

        if ($request->has('level')) {
            $query->where('level', (string) $request->query('level'));
        }

        if (!$request->has('all')) {
            $latest = Prediction::orderByDesc('predicted_at')->first();
            if ($latest) {
                $query->where('predicted_at', $latest->predicted_at);
            }
        }

        $limit = 200;
        if ($request->has('limit')) {
            $limit = min(max((int) $request->query('limit'), 1), 1000);
        }

        $rows = $query
            ->orderBy('for_date')
            ->orderBy('district')
            ->limit($limit)
            ->get();

        return response()->json($rows);
    }

    //la prediccion mas nueva
    public function latest()
    {
        $row = Prediction::orderByDesc('predicted_at')->first();

        return response()->json($row);
    }

    //ultimas 14 predicciones de un distrito
    public function byDistrict(string $district)
    {
        $districtNum = (int) $district;

        $rows = Prediction::where('district', $districtNum)
            ->orderByDesc('predicted_at')
            ->orderBy('for_date')
            ->limit(14)
            ->get();

        return response()->json($rows);
    }
}
