<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\History;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RouteController extends Controller
{
    public function searchLocation(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $limit = max(1, min((int) $request->query('limit', 5), 10));

        if (!$q || mb_strlen($q) < 2) {
            return response()->json([
                'message' => 'q is required (min 2 chars)'
            ], 422);
        }

        $apiKey = env('ORS_API_KEY');

        if (!$apiKey) {
            return response()->json([
                'error' => 'server_misconfigured',
                'message' => 'ORS_API_KEY is missing in .env',
            ], 500);
        }

        $response = Http::withHeaders([
            'Authorization' => $apiKey,
        ])->get('https://api.openrouteservice.org/geocode/search', [
            'text' => $q . ', Madrid, España',
            'size' => $limit,
            'lang' => 'es',

            // Centro de Madrid para priorizar resultados cercanos.
            'focus.point.lat' => 40.4167,
            'focus.point.lon' => -3.7033,

            // Rectángulo aproximado de Madrid y alrededores.
            'boundary.rect.min_lon' => -3.95,
            'boundary.rect.min_lat' => 40.25,
            'boundary.rect.max_lon' => -3.45,
            'boundary.rect.max_lat' => 40.65,

            'boundary.country' => 'ES',
        ]);

        if ($response->failed()) {
            return response()->json([
                'message' => 'ORS geocoding error',
                'status' => $response->status(),
                'details' => $response->json(),
            ], 502);
        }

        $features = $response->json()['features'] ?? [];

        $results = collect($features)->map(function ($feature) {
            $properties = $feature['properties'] ?? [];
            $coordinates = $feature['geometry']['coordinates'] ?? [null, null];

            return [
                'id' => $properties['id'] ?? null,
                'text' => $properties['label'] ?? $properties['name'] ?? 'Ubicación',
                'name' => $properties['name'] ?? null,
                'type' => $properties['layer'] ?? null,
                'district' => $properties['localadmin'] ?? $properties['county'] ?? null,
                'region' => $properties['region'] ?? null,
                'country' => $properties['country'] ?? null,
                'lon' => $coordinates[0],
                'lat' => $coordinates[1],
            ];
        })->filter(function ($result) {
            return $result['lat'] !== null && $result['lon'] !== null;
        })->values();

        return response()->json([
            'query' => $q,
            'results' => $results,
        ]);
    }

    public function preview(Request $request)
    {
        $data = $request->validate([
            'origin.lon' => 'required|numeric|between:-180,180',
            'origin.lat' => 'required|numeric|between:-90,90',
            'destination.lon' => 'required|numeric|between:-180,180',
            'destination.lat' => 'required|numeric|between:-90,90',
            'profile' => 'sometimes|string|in:driving-car,driving-hgv,cycling-regular,foot-walking',
        ]);

        $include = explode(',', (string) $request->query('include', 'summary'));

        $ors = $this->callOrsDirections(
            $data['profile'] ?? 'driving-car',
            $data['origin'],
            $data['destination'],
            in_array('steps', $include),
            in_array('geometry', $include),
            in_array('extras', $include)
        );

        if (isset($ors['error'])) {
            return response()->json($ors, 502);
        }

        $route0 = $ors['routes'][0] ?? null;

        if (!$route0) {
            return response()->json([
                'message' => 'No route found',
                'details' => $ors,
            ], 404);
        }

        return response()->json($this->buildRouteResponse($route0, $include));
    }

    public function calculate(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id,deleted_at,NULL',
            'origin.lon' => 'required|numeric|between:-180,180',
            'origin.lat' => 'required|numeric|between:-90,90',
            'destination.lon' => 'required|numeric|between:-180,180',
            'destination.lat' => 'required|numeric|between:-90,90',
            'profile' => 'sometimes|string|in:driving-car,driving-hgv,cycling-regular,foot-walking',
        ]);

        $include = explode(',', (string) $request->query('include', 'summary'));

        $ors = $this->callOrsDirections(
            $data['profile'] ?? 'driving-car',
            $data['origin'],
            $data['destination'],
            true,
            true,
            false
        );

        if (isset($ors['error'])) {
            return response()->json($ors, 502);
        }

        $route0 = $ors['routes'][0] ?? null;

        if (!$route0) {
            return response()->json([
                'message' => 'No route found',
                'details' => $ors,
            ], 404);
        }

        $history = History::create([
            'user_id' => $data['user_id'],
            'origin_lat' => $data['origin']['lat'],
            'origin_lon' => $data['origin']['lon'],
            'dest_lat' => $data['destination']['lat'],
            'dest_lon' => $data['destination']['lon'],
            'distance_km' => round(($route0['summary']['distance'] ?? 0) / 1000, 2),
            'duration_min' => round(($route0['summary']['duration'] ?? 0) / 60, 2),
        ]);

        $out = $this->buildRouteResponse($route0, $include);
        $out['history_id'] = $history->id;

        return response()->json($out, 201);
    }

    public function detail(History $historyId)
    {
        return response()->json($historyId);
    }

    public function history(User $userId)
    {
        return response()->json(
            $userId->history()->orderByDesc('created_at')->get()
        );
    }

    public function deleteHistory(User $userId, History $historyId)
    {
        if ($historyId->user_id !== $userId->id) {
            abort(403);
        }

        $historyId->delete();

        return response()->json(['ok' => true]);
    }

    public function favorites(User $userId)
    {
        return response()->json(
            $userId->favorites()->with('history')->get()
        );
    }

    public function addFavorite(Request $request, User $userId)
    {
        $data = $request->validate([
            'history_id' => 'required|integer|exists:history,id,user_id,' . $userId->id,
        ]);

        $favorite = Favorite::firstOrCreate([
            'user_id' => $userId->id,
            'history_id' => $data['history_id'],
        ]);

        return response()->json($favorite, 201);
    }

    public function removeFavorite(User $userId, Favorite $favoriteId)
    {
        if ($favoriteId->user_id !== $userId->id) {
            abort(403);
        }

        $favoriteId->delete();

        return response()->json(['ok' => true]);
    }

    private function callOrsDirections($profile, $origin, $destination, $steps, $geom, $extras)
    {
        $apiKey = env('ORS_API_KEY');

        if (!$apiKey) {
            return [
                'error' => 'server_misconfigured',
                'message' => 'ORS_API_KEY is missing in .env',
            ];
        }

        $response = Http::withHeaders([
            'Authorization' => $apiKey,
            'Content-Type' => 'application/json',
        ])->post("https://api.openrouteservice.org/v2/directions/{$profile}/json", [
            'coordinates' => [
                [(float) $origin['lon'], (float) $origin['lat']],
                [(float) $destination['lon'], (float) $destination['lat']],
            ],
            'instructions' => $steps,
            'language' => 'es',
            'geometry' => $geom,
        ]);

        if ($response->failed()) {
            return [
                'error' => 'ors_error',
                'status' => $response->status(),
                'details' => $response->json(),
            ];
        }

        return $response->json();
    }

    private function buildRouteResponse($route, $include)
    {
        $out = [];

        if (in_array('summary', $include)) {
            $out['summary'] = [
                'distance_km' => round(($route['summary']['distance'] ?? 0) / 1000, 2),
                'duration_min' => round(($route['summary']['duration'] ?? 0) / 60, 1),
            ];
        }

        if (in_array('steps', $include)) {
            $out['steps'] = collect($route['segments'] ?? [])->pluck('steps')->flatten(1);
        }

        if (in_array('geometry', $include)) {
            $out['geometry'] = $route['geometry'] ?? null;
        }

        return $out;
    }
}