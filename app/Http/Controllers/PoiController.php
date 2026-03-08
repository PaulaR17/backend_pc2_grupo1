<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PoiController extends Controller
{
    private function orsApiKeyOrFail()
    {
        $apiKey = env('ORS_API_KEY');
        if (!$apiKey) {
            abort(response()->json([
                'error' => 'server_misconfigured',
                'message' => 'ORS_API_KEY is missing in .env',
            ], 500));
        }
        return $apiKey;
    }

    private function postPois(array $payload)
    {
        $apiKey = $this->orsApiKeyOrFail();

        $url = "https://api.openrouteservice.org/pois";

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: {$apiKey}",
                "Content-Type: application/json",
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 25,
        ]);

        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return [502, [
                'error' => 'ors_unreachable',
                'message' => $curlErr ?: 'Unknown cURL error',
            ]];
        }

        $json = json_decode($raw, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            return [502, [
                'error' => 'ors_error',
                'status' => $httpCode,
                'details' => $json ?? $raw,
            ]];
        }

        return [200, $json];
    }

    /**
     * GET /api/pois/categories
     * Devuelve lista de category_group_ids y category_ids.
     */
    public function categories(Request $request)
    {
        [$status, $data] = $this->postPois([
            'request' => 'list',
        ]);

        return response()->json($data, $status);
    }

    public function search(Request $request)
    {
        $validated = $request->validate([
            'center.lon' => 'required|numeric|between:-180,180',
            'center.lat' => 'required|numeric|between:-90,90',
            'radius_m' => 'sometimes|integer|min:1|max:2000', 
            'limit' => 'sometimes|integer|min:1|max:200',
            'sortby' => 'sometimes|string|in:distance,category',
            'filters' => 'sometimes|array',
            'filters.category_group_ids' => 'sometimes|array',
            'filters.category_group_ids.*' => 'integer|min:1',
            'filters.category_ids' => 'sometimes|array',
            'filters.category_ids.*' => 'integer|min:1',
        ]);

        $radius = (int)($validated['radius_m'] ?? 500);
        $limit = (int)($validated['limit'] ?? 50);
        $sortby = $validated['sortby'] ?? 'distance';
        $filters = $validated['filters'] ?? [];

        $payload = [
            'request' => 'pois',
            'geometry' => [
                'geojson' => [
                    'type' => 'Point',
                    'coordinates' => [
                        (float)$validated['center']['lon'],
                        (float)$validated['center']['lat'],
                    ],
                ],
                'buffer' => $radius,
            ],
            'limit' => $limit,
            'sortby' => $sortby,
        ];

        if (!empty($filters)) {
            $payload['filters'] = $filters;
        }

        [$status, $data] = $this->postPois($payload);

        return response()->json($data, $status);
    }

    /**
     * POST /api/pois/stats
     * Igual que search, pero devuelve estadísticas por categorías/grupos.
     */
    public function stats(Request $request)
    {
        $validated = $request->validate([
            'center.lon' => 'required|numeric|between:-180,180',
            'center.lat' => 'required|numeric|between:-90,90',
            'radius_m' => 'sometimes|integer|min:1|max:2000',
            'filters' => 'sometimes|array',
            'filters.category_group_ids' => 'sometimes|array',
            'filters.category_group_ids.*' => 'integer|min:1',
            'filters.category_ids' => 'sometimes|array',
            'filters.category_ids.*' => 'integer|min:1',
        ]);

        $radius = (int)($validated['radius_m'] ?? 500);
        $filters = $validated['filters'] ?? [];

        $payload = [
            'request' => 'stats',
            'geometry' => [
                'geojson' => [
                    'type' => 'Point',
                    'coordinates' => [
                        (float)$validated['center']['lon'],
                        (float)$validated['center']['lat'],
                    ],
                ],
                'buffer' => $radius,
            ],
        ];

        if (!empty($filters)) {
            $payload['filters'] = $filters;
        }

        [$status, $data] = $this->postPois($payload);

        return response()->json($data, $status);
    }
}