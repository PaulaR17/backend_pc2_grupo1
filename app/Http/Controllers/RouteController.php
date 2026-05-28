<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\HasRiskZones;
use App\Models\Favorite;
use App\Models\History;
use App\Models\User;
use App\Services\RewardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RouteController extends Controller
{
    use HasRiskZones;

    //autocompletado mezclando distritos locales y geocode de ORS.
    //pedimos a ORS varios resultados extra para que las calles concretas
    //tengan sitio aunque tambien aparezca algun distrito local que coincida
    public function searchLocation(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $limit = max(1, min((int) $request->query('limit', 8), 15));
        $respuesta = null;

        if (!$q || mb_strlen($q) < 2) {
            $respuesta = response()->json([
                'query' => $q,
                'results' => $this->popularMadridZones(),
            ]);
        } else {
            $localResults = $this->localMadridSuggestions($q);
            //pedimos a ORS el limite completo: queremos que entren calles aunque
            //haya coincidencias locales de distrito
            $orsResults = $this->orsLocationSuggestions($q, $limit);

            $merged = collect($localResults)
                ->merge($orsResults)
                ->unique(function ($item) {
                    //clave para detectar duplicados: texto + coords redondeadas
                    return strtolower($item['text']) . '|' . round($item['lat'], 5) . '|' . round($item['lon'], 5);
                })
                ->take($limit)
                ->values();

            $respuesta = response()->json([
                'query' => $q,
                'results' => $merged,
            ]);
        }

        return $respuesta;
    }

    //llama al geocode de ORS limitado a Madrid
    private function orsLocationSuggestions(string $query, int $limit)
    {
        $apiKey = env('ORS_API_KEY');
        $resultado = collect([]);

        if ($apiKey) {
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

            if (!$response->failed()) {
                $features = $response->json()['features'] ?? [];

                $resultado = collect($features)->map(function ($feature) {
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
                    //ya filtramos por bounding box + country=ES en la peticion;
                    //aqui solo descartamos resultados sin coordenadas validas.
                    //antes exigiamos que el texto contuviera "madrid", pero eso
                    //tiraba calles concretas cuyo label no incluia la palabra.
                    $valido = $result['lat'] !== null && $result['lon'] !== null;

                    return $valido;
                })->values();
            }
        }

        return $resultado;
    }

    //busca dentro de los distritos predefinidos
    private function localMadridSuggestions(string $query)
    {
        $normalizedQuery = mb_strtolower($query);

        $resultado = collect($this->popularMadridZones())
            ->filter(function ($zone) use ($normalizedQuery) {
                $coincide = str_contains(mb_strtolower($zone['name']), $normalizedQuery)
                    || str_contains(mb_strtolower($zone['text']), $normalizedQuery);

                return $coincide;
            })
            ->values();

        return $resultado;
    }

    //distritos y zonas populares de Madrid hardcodeados
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

    //previsualiza una ruta sin guardarla en historial
    public function preview(Request $request)
    {
        $data = $request->validate([
            'origin.lon' => 'required|numeric|between:-180,180',
            'origin.lat' => 'required|numeric|between:-90,90',
            'destination.lon' => 'required|numeric|between:-180,180',
            'destination.lat' => 'required|numeric|between:-90,90',
            'profile' => 'sometimes|string|in:driving-car,driving-hgv,cycling-regular,foot-walking',
            //lista opcional de IDs de distrito a evitar (1..21)
            'avoid_districts' => 'sometimes|array',
            'avoid_districts.*' => 'integer|between:1,21',
        ]);

        $include = explode(',', (string) $request->query('include', 'summary'));

        $ors = $this->callOrsDirections(
            $data['profile'] ?? 'driving-car',
            $data['origin'],
            $data['destination'],
            in_array('steps', $include),
            in_array('geometry', $include),
            in_array('extras', $include),
            $data['avoid_districts'] ?? []
        );

        $respuesta = null;

        if (isset($ors['error'])) {
            $respuesta = response()->json($ors, 502);
        } else {
            $route0 = $ors['routes'][0] ?? null;

            if (!$route0) {
                $respuesta = response()->json([
                    'message' => 'No route found',
                    'details' => $ors,
                ], 404);
            } else {
                $respuesta = response()->json(
                    $this->buildRouteResponse($route0, $include, $data['origin'], $data['destination'])
                );
            }
        }

        return $respuesta;
    }

    //calcula la ruta y la guarda en el historial
    public function calculate(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|integer|exists:users,id,deleted_at,NULL',
            'origin.lon' => 'required|numeric|between:-180,180',
            'origin.lat' => 'required|numeric|between:-90,90',
            'origin.label' => 'sometimes|string|max:255',
            'destination.lon' => 'required|numeric|between:-180,180',
            'destination.lat' => 'required|numeric|between:-90,90',
            'destination.label' => 'sometimes|string|max:255',
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

        $respuesta = null;

        if (isset($ors['error'])) {
            $respuesta = response()->json($ors, 502);
        } else {
            $route0 = $ors['routes'][0] ?? null;

            if (!$route0) {
                $respuesta = response()->json([
                    'message' => 'No route found',
                    'details' => $ors,
                ], 404);
            } else {
                $history = History::create([
                    'user_id' => $data['user_id'],
                    'origin_lat' => $data['origin']['lat'],
                    'origin_lon' => $data['origin']['lon'],
                    'origin_label' => $data['origin']['label'] ?? null,
                    'dest_lat' => $data['destination']['lat'],
                    'dest_lon' => $data['destination']['lon'],
                    'dest_label' => $data['destination']['label'] ?? null,
                    'distance_km' => round(($route0['summary']['distance'] ?? 0) / 1000, 2),
                    'duration_min' => round(($route0['summary']['duration'] ?? 0) / 60, 2),
                ]);

                $out = $this->buildRouteResponse($route0, $include, $data['origin'], $data['destination']);
                $out['history_id'] = $history->id;

                //recompensa al usuario por calcular una ruta (chapitas + XP)
                $rewards = app(RewardService::class);
                $out['reward'] = $rewards->reward($data['user_id'], 'ROUTE_CALCULATED');

                $respuesta = response()->json($out, 201);
            }
        }

        return $respuesta;
    }

    //ficha de una entrada del historial
    public function detail(History $historyId)
    {
        return response()->json($historyId);
    }

    //historial de rutas, las nuevas arriba
    public function history(User $user)
    {
        $rows = $user->history()->orderByDesc('created_at')->get();

        return response()->json($rows);
    }

    //borra una entrada del historial del usuario
    public function deleteHistory(User $user, History $historyId)
    {
        if ($historyId->user_id !== $user->id) {
            abort(403);
        }

        $historyId->delete();

        return response()->json(['ok' => true]);
    }

    //favoritos del usuario con su entrada de historial
    public function favorites(User $user)
    {
        $rows = $user->favorites()->with('history')->get();

        return response()->json($rows);
    }

    //añade una ruta a favoritos sin duplicar
    public function addFavorite(Request $request, User $user)
    {
        $data = $request->validate([
            'history_id' => 'required|integer|exists:history,id,user_id,' . $user->id,
        ]);

        //buscamos incluyendo soft-deleted: el unique constraint cuenta tambien
        //las filas marcadas como borradas, asi que si el usuario quito el
        //favorito antes hay que restaurarlo en vez de intentar insertar
        $favorite = Favorite::withTrashed()->firstOrNew([
            'user_id' => $user->id,
            'history_id' => $data['history_id'],
        ]);

        if ($favorite->trashed()) {
            $favorite->restore();
        } elseif (!$favorite->exists) {
            $favorite->save();
        }

        //cargamos la relacion history para que el front pinte la fila
        //sin tener que recargar la lista entera
        $favorite->load('history');

        return response()->json($favorite, 201);
    }

    //quita un favorito del usuario
    public function removeFavorite(User $user, Favorite $favoriteId)
    {
        if ($favoriteId->user_id !== $user->id) {
            abort(403);
        }

        $favoriteId->delete();

        return response()->json(['ok' => true]);
    }

    //llamada al directions de ORS. Si nos pasan distritos a evitar, los
    //convertimos a poligonos cuadrados (centrados en el centroide del
    //distrito) y se los pasamos a ORS como "avoid_polygons" para que
    //busque una ruta que los rodee.
    private function callOrsDirections($profile, $origin, $destination, $steps, $geom, $extras, $avoidDistricts = [])
    {
        $apiKey = env('ORS_API_KEY');
        $resultado = null;

        if (!$apiKey) {
            $resultado = [
                'error' => 'server_misconfigured',
                'message' => 'ORS_API_KEY is missing in .env',
            ];
        } else {
            $body = [
                'coordinates' => [
                    [(float) $origin['lon'], (float) $origin['lat']],
                    [(float) $destination['lon'], (float) $destination['lat']],
                ],
                'instructions' => $steps,
                'language' => 'es',
                'geometry' => $geom,
            ];

            //si hay distritos que evitar, montamos un MultiPolygon GeoJSON
            //con un cuadrado por cada distrito y lo añadimos a "options"
            if (!empty($avoidDistricts)) {
                $poligonos = [];
                foreach ($avoidDistricts as $id) {
                    $cuadrado = $this->poligonoEvitarDistrito((int) $id);
                    if ($cuadrado !== null) {
                        $poligonos[] = [$cuadrado];
                    }
                }
                if (!empty($poligonos)) {
                    $body['options'] = [
                        'avoid_polygons' => [
                            'type' => 'MultiPolygon',
                            'coordinates' => $poligonos,
                        ],
                    ];
                }
            }

            $response = Http::withHeaders([
                'Authorization' => $apiKey,
                'Content-Type' => 'application/json',
            ])->post("https://api.openrouteservice.org/v2/directions/{$profile}/json", $body);

            if ($response->failed()) {
                $resultado = [
                    'error' => 'ors_error',
                    'status' => $response->status(),
                    'details' => $response->json(),
                ];
            } else {
                $resultado = $response->json();
            }
        }

        return $resultado;
    }

    //monta la respuesta segun los bloques que pida el cliente. si recibe
    //origen y destino, cruza la ruta con las predicciones de PC1 y devuelve
    //los distritos peligrosos atravesados en "risk_zones".
    private function buildRouteResponse($route, $include, $origin = null, $destination = null)
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

        //zonas peligrosas atravesadas (siempre que tengamos origen y destino)
        if ($origin !== null && $destination !== null) {
            $distritos = $this->distritosEnRuta($origin, $destination);
            $out['risk_zones'] = $this->riesgoDistritos($distritos);
        }

        return $out;
    }

}
