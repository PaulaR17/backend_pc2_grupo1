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
        $limit = max(1, min((int) $request->query('limit', 6), 10));

        if (!$q || mb_strlen($q) < 2) {
            return response()->json([
                'query' => $q,
                'results' => $this->popularMadridZones(),
            ]);
        }

        $localResults = $this->localMadridSuggestions($q);
        $orsResults = $this->orsLocationSuggestions($q, $limit);

        $merged = collect($localResults)
            ->merge($orsResults)
            ->unique(function ($item) {
                return strtolower($item['text']) . '|' . round($item['lat'], 5) . '|' . round($item['lon'], 5);
            })
            ->take($limit)
            ->values();

        return response()->json([
            'query' => $q,
            'results' => $merged,
        ]);
    }

    private function orsLocationSuggestions(string $query, int $limit)
{
    $apiKey = env('ORS_API_KEY');

    if (!$apiKey) {
        return collect([]);
    }

    $response = Http::withHeaders([
        'Authorization' => $apiKey,
    ])->get('https://api.openrouteservice.org/geocode/search', [
        'text' => $query . ', Madrid, España',
        'size' => $limit,
        'lang' => 'es',
        'focus.point.lat' => 40.4167,
        'focus.point.lon' => -3.7033,
        'boundary.rect.min_lon' => -3.95,
        'boundary.rect.min_lat' => 40.25,
        'boundary.rect.max_lon' => -3.45,
        'boundary.rect.max_lat' => 40.65,
        'boundary.country' => 'ES',
    ]);

    if ($response->failed()) {
        return collect([]);
    }

    $features = $response->json()['features'] ?? [];

    return collect($features)->map(function ($feature) {
        $properties = $feature['properties'] ?? [];
        $coordinates = $feature['geometry']['coordinates'] ?? [null, null];

        $label = $properties['label'] ?? $properties['name'] ?? 'Ubicación';
        $name = $properties['name'] ?? $label;

        return [
            'id' => $properties['id'] ?? null,
            'text' => $label,
            'name' => $name,
            'type' => $properties['layer'] ?? 'ors',
            'district' => $properties['localadmin'] ?? $properties['county'] ?? null,
            'region' => $properties['region'] ?? 'Madrid',
            'country' => $properties['country'] ?? 'España',
            'lon' => $coordinates[0],
            'lat' => $coordinates[1],
        ];
    })->filter(function ($result) {
        if ($result['lat'] === null || $result['lon'] === null) {
            return false;
        }


        return str_contains(mb_strtolower($result['text']), 'madrid');
    })->values();
}

    private function localMadridSuggestions(string $query)
    {
        $normalizedQuery = mb_strtolower($query);

        return collect($this->popularMadridZones())
            ->filter(function ($zone) use ($normalizedQuery) {
                return str_contains(mb_strtolower($zone['name']), $normalizedQuery)
                    || str_contains(mb_strtolower($zone['text']), $normalizedQuery);
            })
            ->values();
    }

    private function popularMadridZones()
    {
        return [
            [
                'id' => 'local-centro',
                'text' => 'Centro, Madrid, España',
                'name' => 'Centro',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.4150,
                'lon' => -3.7074,
            ],
            [
                'id' => 'local-arganzuela',
                'text' => 'Arganzuela, Madrid, España',
                'name' => 'Arganzuela',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.3982,
                'lon' => -3.6950,
            ],
            [
                'id' => 'local-retiro',
                'text' => 'Retiro, Madrid, España',
                'name' => 'Retiro',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.4115,
                'lon' => -3.6782,
            ],
            [
                'id' => 'local-salamanca',
                'text' => 'Salamanca, Madrid, España',
                'name' => 'Salamanca',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.4270,
                'lon' => -3.6812,
            ],
            [
                'id' => 'local-chamartin',
                'text' => 'Chamartín, Madrid, España',
                'name' => 'Chamartín',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.4593,
                'lon' => -3.6761,
            ],
            [
                'id' => 'local-tetuan',
                'text' => 'Tetuán, Madrid, España',
                'name' => 'Tetuán',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.4598,
                'lon' => -3.6975,
            ],
            [
                'id' => 'local-chamberi',
                'text' => 'Chamberí, Madrid, España',
                'name' => 'Chamberí',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.4340,
                'lon' => -3.7038,
            ],
            [
                'id' => 'local-moncloa',
                'text' => 'Moncloa-Aravaca, Madrid, España',
                'name' => 'Moncloa-Aravaca',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.4352,
                'lon' => -3.7313,
            ],
            [
                'id' => 'local-latina',
                'text' => 'Latina, Madrid, España',
                'name' => 'Latina',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.4037,
                'lon' => -3.7368,
            ],
            [
                'id' => 'local-carabanchel',
                'text' => 'Carabanchel, Madrid, España',
                'name' => 'Carabanchel',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.3818,
                'lon' => -3.7279,
            ],
            [
                'id' => 'local-usera',
                'text' => 'Usera, Madrid, España',
                'name' => 'Usera',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.3826,
                'lon' => -3.7097,
            ],
            [
                'id' => 'local-puente-vallecas',
                'text' => 'Puente de Vallecas, Madrid, España',
                'name' => 'Puente de Vallecas',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.3869,
                'lon' => -3.6667,
            ],
            [
                'id' => 'local-moratalaz',
                'text' => 'Moratalaz, Madrid, España',
                'name' => 'Moratalaz',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.4072,
                'lon' => -3.6570,
            ],
            [
                'id' => 'local-ciudad-lineal',
                'text' => 'Ciudad Lineal, Madrid, España',
                'name' => 'Ciudad Lineal',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.4457,
                'lon' => -3.6510,
            ],
            [
                'id' => 'local-hortaleza',
                'text' => 'Hortaleza, Madrid, España',
                'name' => 'Hortaleza',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.4744,
                'lon' => -3.6411,
            ],
            [
                'id' => 'local-villaverde',
                'text' => 'Villaverde, Madrid, España',
                'name' => 'Villaverde',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.3459,
                'lon' => -3.7114,
            ],
            [
                'id' => 'local-vicalvaro',
                'text' => 'Vicálvaro, Madrid, España',
                'name' => 'Vicálvaro',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.4042,
                'lon' => -3.6081,
            ],
            [
                'id' => 'local-san-blas',
                'text' => 'San Blas-Canillejas, Madrid, España',
                'name' => 'San Blas-Canillejas',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.4289,
                'lon' => -3.6097,
            ],
            [
                'id' => 'local-barajas',
                'text' => 'Barajas, Madrid, España',
                'name' => 'Barajas',
                'type' => 'district',
                'district' => 'Madrid',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.4737,
                'lon' => -3.5796,
            ],
            [
                'id' => 'local-parque-retiro',
                'text' => 'Parque de El Retiro, Madrid, España',
                'name' => 'Parque de El Retiro',
                'type' => 'poi',
                'district' => 'Retiro',
                'region' => 'Madrid',
                'country' => 'España',
                'lat' => 40.4153,
                'lon' => -3.6844,
            ],
        ];
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