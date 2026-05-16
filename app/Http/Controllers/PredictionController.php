<?php

namespace App\Http\Controllers;

use App\Models\Prediction;
use Illuminate\Http\Request;

// Endpoints públicos para consultar las predicciones generadas por PC1.
class PredictionController extends Controller
{
    // Listado filtrable por distrito, target, fecha o nivel.
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

        // Por defecto mostramos solo las del modelo y target más reciente
        // para no devolver miles de filas sin filtrar.
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

    // Predicción más reciente (la que el frontend pinta por defecto).
    public function latest()
    {
        $row = Prediction::orderByDesc('predicted_at')->first();

        return response()->json($row);
    }

    // Últimas predicciones para un distrito concreto.
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
