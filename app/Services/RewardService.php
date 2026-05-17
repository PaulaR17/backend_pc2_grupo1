<?php

namespace App\Services;

use App\Models\Pet;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Model;

//centraliza otorgar recompensas (chapitas + XP) por las acciones del usuario:
//calcular ruta, reportar incidencia, etc. asi el ciclo "ganar -> gastar" queda
//en un solo sitio y los controladores no se ensucian con logica de gamificacion
class RewardService
{
    //XP necesario por nivel. nivel 1 -> 0..99, nivel 2 -> 100..199, etc.
    private const XP_POR_NIVEL = 100;

    //recompensas estandar por accion (chapitas, xp)
    private const PREMIOS = [
        'ROUTE_CALCULATED' => ['coins' => 5,  'xp' => 3],
        'INCIDENT_REPORTED' => ['coins' => 15, 'xp' => 10],
        'HOME_SET' => ['coins' => 5, 'xp' => 5],
        'WORK_SET' => ['coins' => 5, 'xp' => 5],
    ];

    //da las chapitas y el XP que correspondan al usuario por el tipo de accion.
    //devuelve un resumen con lo añadido para que el cliente lo muestre.
    public function reward(int $userId, string $accion): array
    {
        $resumen = [
            'coins' => 0,
            'xp' => 0,
            'level_up' => false,
            'new_level' => null,
            'action' => $accion,
        ];

        if (isset(self::PREMIOS[$accion])) {
            $premio = self::PREMIOS[$accion];

            //transaccion positiva = chapitas ganadas
            Transaction::create([
                'user_id' => $userId,
                'type' => $accion,
                'amount' => $premio['coins'],
            ]);
            $resumen['coins'] = $premio['coins'];

            //subir XP a la mascota (la crea si aun no tiene)
            $pet = Pet::firstOrCreate(
                ['user_id' => $userId],
                ['name' => 'Eco', 'level' => 1, 'xp' => 0, 'image' => null]
            );

            $xpAnterior = (int) $pet->xp;
            $nivelAnterior = (int) $pet->level;
            $xpNuevo = $xpAnterior + $premio['xp'];
            $nivelNuevo = max(1, intdiv($xpNuevo, self::XP_POR_NIVEL) + 1);

            $pet->xp = $xpNuevo;
            $pet->level = $nivelNuevo;
            $pet->updated_at = now();
            $pet->save();

            $resumen['xp'] = $premio['xp'];
            $resumen['new_level'] = $nivelNuevo;
            $resumen['level_up'] = $nivelNuevo > $nivelAnterior;
        }

        return $resumen;
    }

    //balance actual = suma de todas las transactions del usuario (positivas
    //por recompensas, negativas por compras en la tienda)
    public function balance(int $userId): int
    {
        $total = (int) Transaction::where('user_id', $userId)->sum('amount');
        return $total;
    }
}
