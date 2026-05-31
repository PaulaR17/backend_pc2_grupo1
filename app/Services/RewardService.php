<?php

namespace App\Services;

use App\Models\Pet;
use App\Models\Transaction;
use App\Models\Badge;
use Illuminate\Database\Eloquent\Model;

//centraliza otorgar recompensas (chapitas + XP) por las acciones del usuario
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

    //da las chapitas y el XP que correspondan al usuario por el tipo de accion
    public function reward(int $userId, string $accion): array
    {
        $resumen = [
            'coins' => 0,
            'xp' => 0,
            'level_up' => false,
            'new_level' => null,
            'action' => $accion,
            //codigos de las chapitas que acaba de conseguir con esta accion
            'new_badges' => [],
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

            //comprobamos si por esta accion se ha ganado alguna chapita nueva
            $resumen['new_badges'] = $this->concederChapitas($userId, $accion);
        }

        return $resumen;
    }

    //da las chapitas que correspondan segun cuantas veces ha hecho la accion
    private function concederChapitas(int $userId, string $accion): array
    {
        $nuevas = [];

        //el contador ya incluye la accion que se acaba de hacer
        $veces = Transaction::where('user_id', $userId)
            ->where('type', $accion)
            ->count();

        if ($accion === 'ROUTE_CALCULATED') {
            if ($veces >= 1 && $this->darChapita($userId, 'FIRST_ROUTE')) {
                $nuevas[] = 'FIRST_ROUTE';
            }
            if ($veces >= 10 && $this->darChapita($userId, 'EXPLORER')) {
                $nuevas[] = 'EXPLORER';
            }
        } else if ($accion === 'INCIDENT_REPORTED') {
            if ($veces >= 3 && $this->darChapita($userId, 'ECO_DRIVER')) {
                $nuevas[] = 'ECO_DRIVER';
            }
        }

        return $nuevas;
    }

    //crea la chapita solo si el usuario no la tenia ya
    private function darChapita(int $userId, string $code): bool
    {
        $esNueva = false;
        $yaLaTiene = Badge::where('user_id', $userId)
            ->where('code', $code)
            ->exists();

        if (!$yaLaTiene) {
            Badge::create([
                'user_id' => $userId,
                'code' => $code,
                'earned_at' => now(),
            ]);
            $esNueva = true;
        }

        return $esNueva;
    }

    //balance actual = suma de todas las transactions del usuario
    public function balance(int $userId): int
    {
        $total = (int) Transaction::where('user_id', $userId)->sum('amount');
        return $total;
    }
}
