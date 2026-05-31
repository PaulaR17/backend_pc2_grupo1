<?php

namespace Database\Seeders;

use App\Models\Badge;
use App\Models\Inventory;
use App\Models\Item;
use App\Models\User;
use App\Models\VehicleLabel;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

// Seeder principal: crea usuarios, items, inventario y logros de prueba
// para poder ver la app llena de contenido sin tener que crear nada a mano.
//
// Credenciales (contraseña en todos: password123):
//   - admin@ecotraffic.com   (rol ADMIN)
//   - usuario@ecotraffic.com (rol USER)
//   - paula@ecotraffic.com   (rol USER)
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->crearUsuarios();
        $this->crearEtiquetas();
        $this->crearItems();
        $this->crearInventarioYLogros();
    }

    //etiquetas ambientales (distintivos DGT) para elegir en el perfil
    private function crearEtiquetas(): void
    {
        $etiquetas = [
            ['name' => '0',   'description' => 'Cero emisiones: eléctricos e hidrógeno.'],
            ['name' => 'ECO', 'description' => 'Híbridos y vehículos de gas.'],
            ['name' => 'C',   'description' => 'Gasolina desde 2006 y diésel desde 2014.'],
            ['name' => 'B',   'description' => 'Gasolina desde 2000 y diésel desde 2006.'],
        ];

        foreach ($etiquetas as $datos) {
            VehicleLabel::updateOrCreate(
                ['name' => $datos['name']],
                $datos
            );
        }
    }

    // -------------------------------------------------------
    //  USUARIOS
    // -------------------------------------------------------
    private function crearUsuarios(): void
    {
        $usuarios = [
            [
                'name'          => 'Admin EcoTraffic',
                'mail'          => 'admin@ecotraffic.com',
                'password_hash' => Hash::make('password123'),
                'rol'           => 'ADMIN',
                'status'        => true,
            ],
            [
                'name'          => 'Usuario Demo',
                'mail'          => 'usuario@ecotraffic.com',
                'password_hash' => Hash::make('password123'),
                'rol'           => 'USER',
                'status'        => true,
            ],
            [
                'name'          => 'Paula',
                'mail'          => 'paula@ecotraffic.com',
                'password_hash' => Hash::make('password123'),
                'rol'           => 'USER',
                'status'        => true,
            ],
        ];

        foreach ($usuarios as $datos) {
            User::updateOrCreate(
                ['mail' => $datos['mail']],
                $datos
            );
        }
    }

    // -------------------------------------------------------
    //  ITEMS (catálogo de la tienda)
    // -------------------------------------------------------
    private function crearItems(): void
    {
        //el campo "image" guarda un emoji o ruta. usamos emoji para no
        //depender de imagenes externas y para que se vea bien en cualquier UI.
        $items = [
            [
                'name'        => 'Gorra clásica',
                'type'        => 'HAT',
                'rarity'      => 'common',
                'description' => 'Una gorra sencilla para tu mascota.',
                'image'       => '🧢',
                'price'       => 50,
                'active'      => true,
            ],
            [
                'name'        => 'Sombrero de copa',
                'type'        => 'HAT',
                'rarity'      => 'epic',
                'description' => 'Para una mascota con estilo.',
                'image'       => '🎩',
                'price'       => 250,
                'active'      => true,
            ],
            [
                'name'        => 'Gafas de sol',
                'type'        => 'GLASSES',
                'rarity'      => 'rare',
                'description' => 'Protección extra contra el sol madrileño.',
                'image'       => '🕶️',
                'price'       => 120,
                'active'      => true,
            ],
            [
                'name'        => 'Gafas redondas',
                'type'        => 'GLASSES',
                'rarity'      => 'common',
                'description' => 'Modelo intelectual.',
                'image'       => '👓',
                'price'       => 60,
                'active'      => true,
            ],
            [
                'name'        => 'Traje deportivo',
                'type'        => 'SUIT',
                'rarity'      => 'rare',
                'description' => 'Perfecto para rutas largas.',
                'image'       => '👕',
                'price'       => 200,
                'active'      => true,
            ],
            [
                'name'        => 'Traje legendario',
                'type'        => 'SUIT',
                'rarity'      => 'legendary',
                'description' => 'Solo los mejores conductores lo lucen.',
                'image'       => '🦺',
                'price'       => 500,
                'active'      => true,
            ],
        ];

        foreach ($items as $datos) {
            Item::updateOrCreate(
                ['name' => $datos['name']],
                $datos
            );
        }
    }

    // -------------------------------------------------------
    //  INVENTARIO + LOGROS (solo para Paula y el usuario demo)
    // -------------------------------------------------------
    private function crearInventarioYLogros(): void
    {
        $paula = User::where('mail', 'paula@ecotraffic.com')->first();
        $usuario = User::where('mail', 'usuario@ecotraffic.com')->first();
        $gorra = Item::where('name', 'Gorra clásica')->first();
        $gafas = Item::where('name', 'Gafas redondas')->first();

        if ($paula && $gorra) {
            // Insert raw para evitar problemas con columnas opcionales.
            DB::table('inventory')->updateOrInsert(
                ['user_id' => $paula->id, 'item_id' => $gorra->id],
                ['quantity' => 1]
            );
        }

        if ($paula && $gafas) {
            DB::table('inventory')->updateOrInsert(
                ['user_id' => $paula->id, 'item_id' => $gafas->id],
                ['quantity' => 1]
            );
        }

        if ($usuario && $gorra) {
            DB::table('inventory')->updateOrInsert(
                ['user_id' => $usuario->id, 'item_id' => $gorra->id],
                ['quantity' => 2]
            );
        }

        // Logros: una insignia para Paula y otra para el usuario demo.
        if ($paula) {
            Badge::updateOrCreate(
                ['user_id' => $paula->id, 'code' => 'FIRST_ROUTE'],
                ['earned_at' => now()]
            );
        }

        if ($usuario) {
            Badge::updateOrCreate(
                ['user_id' => $usuario->id, 'code' => 'ECO_DRIVER'],
                ['earned_at' => now()]
            );
        }
    }
}
