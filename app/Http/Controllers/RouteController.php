<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\History;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http; // <--- Importante para la API externa

class RouteController extends Controller
{
    public function searchLocation(Request $request)
{
    $q = $request->query('q');
    $limit = max(1, min((int) $request->query('limit', 5), 20));

    if (!$q || mb_strlen($q) < 3) {
        return response()->json(['message' => 'q is required (min 3 chars)'], 422);
    }

    $url = "https://api.openrouteservice.org/geocode/search";
    
    $response = Http::withHeaders(['Authorization' => env('ORS_API_KEY')])
        ->get($url, [
            'text' => $q . ', Madrid, España',
            'size' => $limit,
            'lang' => 'es',
            'boundary.country' => 'ES',
            'boundary.rect.min_lon' => -3.90,
            'boundary.rect.min_lat' => 40.30,
            'boundary.rect.max_lon' => -3.50,
            'boundary.rect.max_lat' => 40.60,
        ]);

    if ($response->failed()) {
        return response()->json([
            'message' => 'ORS Error',
            'details' => $response->json()
        ], 502);
    }

    $results = collect($response->json()['features'] ?? [])->map(function ($f) {
        return [
            'text' => $f['properties']['label'] ?? $f['properties']['name'] ?? 'Ubicación',
            'lon'  => $f['geometry']['coordinates'][0],
            'lat'  => $f['geometry']['coordinates'][1],
        ];
    });

    return response()->json(['results' => $results]);
}

    /**
     * Vista previa de ruta (Sin guardar en historial).
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

        $include = explode(',', (string) $request->query('include', 'summary'));
        
        $ors = $this->callOrsDirections(
            $data['profile'] ?? 'driving-car',
            $data['origin'],
            $data['destination'],
            in_array('steps', $include),
            in_array('geometry', $include),
            in_array('extras', $include)
        );

        $route0 = $ors['routes'][0] ?? null;
        if (!$route0) return response()->json(['message' => 'No route found'], 404);

        return response()->json($this->buildRouteResponse($route0, $include));
    }


    public function calculate(Request $request)
    {
        // Nota: user_id viene en el body
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id,deleted_at,NULL',
            'origin.lon' => 'required|numeric|between:-180,180',
            'origin.lat' => 'required|numeric|between:-90,90',
            'destination.lon' => 'required|numeric|between:-180,180',
            'destination.lat' => 'required|numeric|between:-90,90',
            'profile' => 'sometimes|string|in:driving-car,driving-hgv,cycling-regular,foot-walking',
        ]);

        $include = explode(',', (string) $request->query('include', 'summary'));
        $ors = $this->callOrsDirections($data['profile'] ?? 'driving-car', $data['origin'], $data['destination'], true, true, false);

        $route0 = $ors['routes'][0] ?? abort(404, 'No route found');

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
        return response()->json($userId->history()->orderByDesc('created_at')->get());
    }

    public function deleteHistory(User $userId, History $historyId)
    {
        if ($historyId->user_id !== $userId->id) abort(403);
        $historyId->delete();
        return response()->json(['ok' => true]);
    }

    public function favorites(User $userId)
    {
        return response()->json($userId->favorites()->with('history')->get());
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
        if ($favoriteId->user_id !== $userId->id) abort(403);
        
        $favoriteId->delete();
        return response()->json(['ok' => true]);
    }

    private function callOrsDirections($profile, $origin, $destination, $steps, $geom, $extras)
    {
        $response = Http::withHeaders(['Authorization' => env('ORS_API_KEY')])
            ->post("https://api.openrouteservice.org/v2/directions/{$profile}/json", [
                'coordinates' => [[$origin['lon'], $origin['lat']], [$destination['lon'], $destination['lat']]],
                'instructions' => $steps,
                'language' => 'es',
                'geometry' => $geom,
            ]);

        return $response->json();
    }

    private function buildRouteResponse($route, $include)
    {
        $out = [];
        if (in_array('summary', $include)) {
            $out['summary'] = [
                'distance_km' => round($route['summary']['distance'] / 1000, 2),
                'duration_min' => round($route['summary']['duration'] / 60, 1),
            ];
        }
        if (in_array('steps', $include)) {
            $out['steps'] = collect($route['segments'])->pluck('steps')->flatten(1);
        }
        if (in_array('geometry', $include)) $out['geometry'] = $route['geometry'];

        return $out;
    }
}