<?php

namespace App\Http\Controllers\Concerns;

use App\Models\Prediction;

//logica para detectar que distritos atraviesa una ruta y cruzar con las
//predicciones futuras de PC1. compartido entre RouteController (usuarios) y
//GuestSessionController (invitados) para que ambos avisen de zonas peligrosas.
trait HasRiskZones
{
    //centroides aproximados (lat, lon) de los 21 distritos de Madrid.
    //sirven para detectar por que distrito pasa una ruta sin necesitar
    //un GeoJSON completo de los limites administrativos.
    private function centroidesDistritos()
    {
        return [
            1  => ['lat' => 40.4153, 'lon' => -3.7074],   //Centro
            2  => ['lat' => 40.4023, 'lon' => -3.6951],   //Arganzuela
            3  => ['lat' => 40.4080, 'lon' => -3.6810],   //Retiro
            4  => ['lat' => 40.4300, 'lon' => -3.6770],   //Salamanca
            5  => ['lat' => 40.4530, 'lon' => -3.6772],   //Chamartin
            6  => ['lat' => 40.4596, 'lon' => -3.6995],   //Tetuan
            7  => ['lat' => 40.4332, 'lon' => -3.7034],   //Chamberi
            8  => ['lat' => 40.4787, 'lon' => -3.7102],   //Fuencarral-El Pardo
            9  => ['lat' => 40.4350, 'lon' => -3.7466],   //Moncloa-Aravaca
            10 => ['lat' => 40.4031, 'lon' => -3.7472],   //Latina
            11 => ['lat' => 40.3835, 'lon' => -3.7297],   //Carabanchel
            12 => ['lat' => 40.3812, 'lon' => -3.7065],   //Usera
            13 => ['lat' => 40.3877, 'lon' => -3.6633],   //Puente de Vallecas
            14 => ['lat' => 40.4101, 'lon' => -3.6404],   //Moratalaz
            15 => ['lat' => 40.4474, 'lon' => -3.6451],   //Ciudad Lineal
            16 => ['lat' => 40.4742, 'lon' => -3.6489],   //Hortaleza
            17 => ['lat' => 40.3500, 'lon' => -3.6900],   //Villaverde
            18 => ['lat' => 40.3784, 'lon' => -3.6157],   //Villa de Vallecas
            19 => ['lat' => 40.4014, 'lon' => -3.6076],   //Vicalvaro
            20 => ['lat' => 40.4254, 'lon' => -3.6109],   //San Blas-Canillejas
            21 => ['lat' => 40.4708, 'lon' => -3.5800],   //Barajas
        ];
    }

    //devuelve el id del distrito cuyo centroide esta mas cerca del punto dado;
    //distancia euclidiana en grados, suficiente para una ciudad pequeña
    private function distritoMasCercano($lat, $lon)
    {
        $mejor = null;
        $mejor_distancia = PHP_FLOAT_MAX;

        foreach ($this->centroidesDistritos() as $id => $coord) {
            $dlat = $coord['lat'] - (float) $lat;
            $dlon = $coord['lon'] - (float) $lon;
            $distancia = $dlat * $dlat + $dlon * $dlon;
            if ($distancia < $mejor_distancia) {
                $mejor_distancia = $distancia;
                $mejor = $id;
            }
        }

        return $mejor;
    }

    //muestrea la linea recta entre origen y destino en 12 puntos y devuelve
    //la lista (sin repetir) de distritos por los que pasa. aproxima la ruta
    //ORS por una recta; no es exacto pero detecta los distritos atravesados
    //en una ciudad de pocos kilometros como Madrid.
    private function distritosEnRuta($origin, $destination)
    {
        $muestras = 12;
        $distritos = [];
        $lat0 = (float) $origin['lat'];
        $lon0 = (float) $origin['lon'];
        $lat1 = (float) $destination['lat'];
        $lon1 = (float) $destination['lon'];

        for ($i = 0; $i <= $muestras; $i++) {
            $t = $i / $muestras;
            $lat = $lat0 + $t * ($lat1 - $lat0);
            $lon = $lon0 + $t * ($lon1 - $lon0);
            $d = $this->distritoMasCercano($lat, $lon);
            $hay_que_anadir = $d !== null && !in_array($d, $distritos, true);
            if ($hay_que_anadir) {
                $distritos[] = $d;
            }
        }

        return $distritos;
    }

    //consulta predicciones futuras de los distritos dados y devuelve el peor
    //caso por distrito (la probabilidad mas alta), para que el cliente avise
    //al usuario antes de salir
    private function riesgoDistritos($distritos)
    {
        $resultado = [];

        if (!empty($distritos)) {
            $hoy = date('Y-m-d');
            $preds = Prediction::whereIn('district', $distritos)
                ->where('for_date', '>=', $hoy)
                ->where('target_type', 'Accidentes')
                ->get();

            foreach ($preds as $p) {
                $existente = $resultado[$p->district] ?? null;
                $supera = $existente === null || $p->probability > $existente['probability'];
                if ($supera) {
                    $resultado[$p->district] = [
                        'district' => (int) $p->district,
                        'level' => $p->level,
                        'probability' => (float) $p->probability,
                        'for_date' => $p->for_date,
                    ];
                }
            }
        }

        return array_values($resultado);
    }
}
