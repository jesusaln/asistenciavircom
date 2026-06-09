<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PriceList;

class PriceListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $listas = [
            [
                'nombre' => 'Público en General',
                'clave' => 'publico_general',
                'descripcion' => 'Precio base para venta al público general',
                'activa' => true,
                'orden' => 1,
            ],
            [
                'nombre' => 'Técnico',
                'clave' => 'tecnico',
                'descripcion' => 'Precio preferencial para técnicos',
                'activa' => true,
                'orden' => 2,
            ],
            [
                'nombre' => 'Medio Mayoreo',
                'clave' => 'medio_mayoreo',
                'descripcion' => 'Precio para compras de medio mayoreo',
                'activa' => true,
                'orden' => 3,
            ],
            [
                'nombre' => 'Mayoreo',
                'clave' => 'mayoreo',
                'descripcion' => 'Precio para compras de mayoreo',
                'activa' => true,
                'orden' => 4,
            ],
            [
                'nombre' => 'Distribuidor',
                'clave' => 'distribuidor',
                'descripcion' => 'Precio especial para distribuidores autorizados',
                'activa' => true,
                'orden' => 5,
            ],
        ];

        foreach ($listas as $lista) {
            PriceList::updateOrCreate(
                ['clave' => $lista['clave']],
                $lista
            );
        }

        $this->command->info('✓ Listas de Precios base (5)');
    }
}
