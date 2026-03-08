<?php

namespace App\Http\Controllers;

use App\Models\GuestSession;
use Illuminate\Http\Request;

class GuestSessionController extends Controller
{
    private const MAX_SEARCHES = 4;

    public function create(Request $request)
    {
        //se crea una sesión "guest" con session_id (string) y search_count=0
        $sessionId = bin2hex(random_bytes(16)); 

        $session = new GuestSession();
        $session->session_id = $sessionId;
        $session->search_count = 0;
        $session->save();

        return response()->json([
            'session_id' => $session->session_id,
            'search_count' => $session->search_count,
            'remaining' => self::MAX_SEARCHES - $session->search_count,
            'max' => self::MAX_SEARCHES,
        ], 201);
    }

    public function quota(string $sessionId)
    {
        $session = GuestSession::findOrFail($sessionId);

        return response()->json([
            'session_id' => $session->session_id,
            'search_count' => $session->search_count,
            'remaining' => max(0, self::MAX_SEARCHES - $session->search_count),
            'max' => self::MAX_SEARCHES,
        ]);
    }

    /**
     * Query param:
     *   include=summary | summary,steps | summary,geometry | summary,steps,geometry
     *
     * Body:
     * {
     *   "origin": {"lon":..,"lat":..},
     *   "destination": {"lon":..,"lat":..},
     *   "profile":"driving-car"
     * }
     */
    public function calculateRoute(Request $request, string $sessionId)
    {
        $session = GuestSession::findOrFail($sessionId);

        if ($session->search_count >= self::MAX_SEARCHES) {
            return response()->json([
                'error' => 'quota_exceeded',
                'message' => 'Guest quota exceeded. Please register to continue.',
                'session_id' => $session->session_id,
                'search_count' => $session->search_count,
                'remaining' => 0,
                'max' => self::MAX_SEARCHES,
            ], 429);
        }

        $validated = $request->validate([
            'origin.lat' => 'required|numeric|between:-90,90',
            'origin.lon' => 'required|numeric|between:-180,180',
            'destination.lat' => 'required|numeric|between:-90,90',
            'destination.lon' => 'required|numeric|between:-180,180',
            'profile' => 'sometimes|string|in:driving-car,driving-hgv,cycling-regular,foot-walking',
        ]);

        $includeRaw = (string) $request->query('include', 'summary');
        $include = array_values(array_filter(array_map('trim', explode(',', $includeRaw))));

        //flags
        $wantSummary = in_array('summary', $include, true) || empty($include);
        $wantSteps = in_array('steps', $include, true);
        $wantGeometry = in_array('geometry', $include, true);

        $profile = $validated['profile'] ?? 'driving-car';

        $apiKey = env('ORS_API_KEY');
        if (!$apiKey) {
            return response()->json([
                'error' => 'server_misconfigured',
                'message' => 'ORS_API_KEY is missing in .env',
            ], 500);
        }

        $url = "https://api.openrouteservice.org/v2/directions/{$profile}/json";

        $body = [
            'coordinates' => [
                [(float)$validated['origin']['lon'], (float)$validated['origin']['lat']],
                [(float)$validated['destination']['lon'], (float)$validated['destination']['lat']],
            ],
            'instructions' => $wantSteps,
            'geometry' => $wantGeometry,
            'language' => 'es',
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: {$apiKey}",
                "Content-Type: application/json",
            ],
            CURLOPT_POSTFIELDS => json_encode($body),
            CURLOPT_TIMEOUT => 20,
        ]);

        $raw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            return response()->json([
                'error' => 'ors_unreachable',
                'message' => $curlErr ?: 'Unknown cURL error',
            ], 502);
        }

        $json = json_decode($raw, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            return response()->json([
                'error' => 'ors_error',
                'status' => $httpCode,
                'details' => $json ?? $raw,
            ], 502);
        }

        //se gasta 1 SOLO si la api lo ha devuelto bien
        $session->search_count = (int)$session->search_count + 1;
        $session->save();

        //para normalizar la respuesta
        $route0 = $json['routes'][0] ?? null;
        if (!$route0) {
            return response()->json([
                'error' => 'ors_invalid_response',
                'details' => $json,
            ], 502);
        }

        $out = [
            'guest' => true,
            'session_id' => $session->session_id,
            'search_count' => $session->search_count,
            'remaining' => max(0, self::MAX_SEARCHES - $session->search_count),
            'max' => self::MAX_SEARCHES,
        ];

        if ($wantSummary) {
            $summary = $route0['summary'] ?? [];
            $distanceM = (float)($summary['distance'] ?? 0.0);
            $durationS = (float)($summary['duration'] ?? 0.0);

            $out['summary'] = [
                'distance_m' => $distanceM,
                'duration_s' => $durationS,
                'distance_km' => round($distanceM / 1000.0, 3),
                'duration_min' => round($durationS / 60.0, 2),
            ];
        }

        if ($wantSteps) {
            $steps = $route0['segments'][0]['steps'] ?? [];
            $out['steps'] = $steps;
        }

        if ($wantGeometry) {
            $out['geometry'] = $route0['geometry'] ?? null;
        }

        return response()->json($out);
    }
}