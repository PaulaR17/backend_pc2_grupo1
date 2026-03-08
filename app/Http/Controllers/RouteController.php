<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class RouteController extends Controller
{
    /**
     * GET /api/locations/search?q=texto&limit=5
     * Devuelve: [{text, lon, lat}]
     */
    public function searchLocation(Request $request)
    {
        $q = $request->query('q');
        $limit = (int) $request->query('limit', 5);

        if (!$q || mb_strlen($q) < 3) {
            return response()->json([
                'message' => 'q is required (min 3 chars)',
            ], 422);
        }

        $limit = max(1, min($limit, 20));

        $url = "https://api.openrouteservice.org/geocode/search"
            . "?text=" . urlencode($q)
            . "&size=" . $limit
            . "&lang=es";

        $raw = $this->curlGetJson($url);

        $results = [];
        foreach (($raw['features'] ?? []) as $f) {
            $coords = $f['geometry']['coordinates'] ?? null; // [lon, lat]
            if (!$coords || count($coords) < 2) continue;

            $results[] = [
                'text' => $f['properties']['label'] ?? ($f['properties']['name'] ?? null),
                'lon'  => $coords[0],
                'lat'  => $coords[1],
            ];
        }

        return response()->json([
            'results' => $results,
        ]);
    }

    /**
     * POST /api/routes/preview?include=summary,steps,geometry,extras
     * Body:
     * {
     *   "origin": {"lon": -3.70, "lat": 40.41},
     *   "destination": {"lon": -3.68, "lat": 40.45},
     *   "profile": "driving-car"
     * }
     */
    public function preview(Request $request)
    {
        $data = $request->validate([
            'origin.lon' => 'required|numeric|between:-180,180',
            'origin.lat' => 'required|numeric|between:-90,90',
            'destination.lon' => 'required|numeric|between:-180,180',
            'destination.lat' => 'required|numeric|between:-90,90',
            'profile' => 'sometimes|string|in:driving-car,driving-hgv,cycling-regular,foot-walking',
        ]);

        $profile = $data['profile'] ?? 'driving-car';

        //include=summary,steps,geometry,extras
        $include = collect(explode(',', (string) $request->query('include', 'summary')))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->unique()
            ->values();

        $wantSummary  = $include->contains('summary');
        $wantSteps    = $include->contains('steps');
        $wantGeometry = $include->contains('geometry');
        $wantExtras   = $include->contains('extras');

        $ors = $this->callOrsDirections(
            profile: $profile,
            origin: $data['origin'],
            destination: $data['destination'],
            instructions: $wantSteps,
            geometry: $wantGeometry,
            extras: $wantExtras
        );

        $route0 = $ors['routes'][0] ?? null;
        if (!$route0) {
            return response()->json(['message' => 'No route found'], 404);
        }

        $out = [];

        if ($wantSummary) {
            $distanceM = $route0['summary']['distance'] ?? null;
            $durationS = $route0['summary']['duration'] ?? null;

            $out['summary'] = [
                'distance_m' => $distanceM,
                'duration_s' => $durationS,
                'distance_km' => is_numeric($distanceM) ? round($distanceM / 1000, 3) : null,
                'duration_min' => is_numeric($durationS) ? round($durationS / 60, 1) : null,
            ];
        }

        if ($wantSteps) {
            //ORS devuelve steps dentro de segments[] si hay varios segments, los juntamos
            $steps = [];
            foreach (($route0['segments'] ?? []) as $seg) {
                foreach (($seg['steps'] ?? []) as $st) {
                    $steps[] = $st;
                }
            }
            $out['steps'] = $steps;
        }

        if ($wantGeometry) {
            $out['geometry'] = $route0['geometry'] ?? null; 
        }

        if ($wantExtras) {
            $out['extras'] = $route0['extras'] ?? null; 
        }

        return response()->json($out);
    }

    
    public function calculate(Request $request)
    {
        $data = $request->validate([
            'origin.lon' => 'required|numeric|between:-180,180',
            'origin.lat' => 'required|numeric|between:-90,90',
            'destination.lon' => 'required|numeric|between:-180,180',
            'destination.lat' => 'required|numeric|between:-90,90',
            'profile' => 'sometimes|string|in:driving-car,driving-hgv,cycling-regular,foot-walking',
        ]);

        $profile = $data['profile'] ?? 'driving-car';

        $include = collect(explode(',', (string) $request->query('include', 'summary')))
            ->map(fn ($s) => trim($s))
            ->filter()
            ->unique()
            ->values();

        $wantSummary  = $include->contains('summary');
        $wantSteps    = $include->contains('steps');
        $wantGeometry = $include->contains('geometry');
        $wantExtras   = $include->contains('extras');

        $ors = $this->callOrsDirections(
            profile: $profile,
            origin: $data['origin'],
            destination: $data['destination'],
            instructions: $wantSteps,
            geometry: $wantGeometry,
            extras: $wantExtras
        );

        $route0 = $ors['routes'][0] ?? null;
        if (!$route0) {
            return response()->json(['message' => 'No route found'], 404);
        }
        $out = [];

        if ($wantSummary) {
            $distanceM = $route0['summary']['distance'] ?? null;
            $durationS = $route0['summary']['duration'] ?? null;

            $out['summary'] = [
                'distance_m' => $distanceM,
                'duration_s' => $durationS,
                'distance_km' => is_numeric($distanceM) ? round($distanceM / 1000, 3) : null,
                'duration_min' => is_numeric($durationS) ? round($durationS / 60, 1) : null,
            ];
        }

        if ($wantSteps) {
            $steps = [];
            foreach (($route0['segments'] ?? []) as $seg) {
                foreach (($seg['steps'] ?? []) as $st) {
                    $steps[] = $st;
                }
            }
            $out['steps'] = $steps;
        }

        if ($wantGeometry) {
            $out['geometry'] = $route0['geometry'] ?? null;
        }

        if ($wantExtras) {
            $out['extras'] = $route0['extras'] ?? null;
        }

        return response()->json($out);
    }
    private function callOrsDirections(
        string $profile,
        array $origin,
        array $destination,
        bool $instructions,
        bool $geometry,
        bool $extras
    ): array {
        $url = "https://api.openrouteservice.org/v2/directions/{$profile}/json";

        $payload = [
            'coordinates' => [
                [(float) $origin['lon'], (float) $origin['lat']],
                [(float) $destination['lon'], (float) $destination['lat']],
            ],
            'instructions' => $instructions,
            'language' => 'es',
            'geometry' => $geometry,
        ];

        if ($extras) {
            $payload['extra_info'] = ['surface', 'steepness', 'waytype', 'tollways', 'osmid'];
        }

        return $this->curlPostJson($url, $payload);
    }

    
    private function curlGetJson(string $url): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: " . env('ORS_API_KEY'),
                "Accept: application/json",
            ],
            CURLOPT_TIMEOUT => 12,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            abort(502, "ORS curl error: " . $err);
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            abort(502, "ORS invalid JSON");
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            abort(502, "ORS error ({$httpCode}): " . json_encode($decoded));
        }

        return $decoded;
    }

    /**
     * cURL POST que devuelve array (json_decode)
     */
    private function curlPostJson(string $url, array $payload): array
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: " . env('ORS_API_KEY'),
                "Content-Type: application/json",
                "Accept: application/json",
            ],
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 12,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            abort(502, "ORS curl error: " . $err);
        }

        $decoded = json_decode($response, true);
        if (!is_array($decoded)) {
            abort(502, "ORS invalid JSON");
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            abort(502, "ORS error ({$httpCode}): " . json_encode($decoded));
        }

        return $decoded;
    }
}