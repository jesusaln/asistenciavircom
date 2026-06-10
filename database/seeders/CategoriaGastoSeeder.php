<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CategoriaGasto;

class CategoriaGastoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categorias = [
            ['nombre' => 'Materiales', 'descripcion' => 'Materiales e insumos para el proyecto', 'codigo' => 'MAT'],
            ['nombre' => 'Viáticos', 'descripcion' => 'Gastos de viaje, hospedaje y movilidad', 'codigo' => 'VIA'],
            ['nombre' => 'Combustible', 'descripcion' => 'Gasolina y lubricantes para vehículos', 'codigo' => 'COM'],
            ['nombre' => 'Herramientas', 'descripcion' => 'Compra o renta de herramientas menores', 'codigo' => 'HER'],
            ['nombre' => 'Transporte', 'descripcion' => 'Fletes y servicios de transporte', 'codigo' => 'TRA'],
            ['nombre' => 'Comida', 'descripcion' => 'Alimentos y refrigerios para el equipo', 'codigo' => 'ALM'],
            ['nombre' => 'Mano de Obra Externa', 'descripcion' => 'Servicios de contratistas externos', 'codigo' => 'MOE'],
            ['nombre' => 'Otros', 'descripcion' => 'Gastos varios no clasificados', 'codigo' => 'OTR'],
        ];

        foreach ($categorias as $cat) {
            CategoriaGasto::updateOrCreate(
                ['nombre' => $cat['nombre']],
                [
                    'descripcion' => $cat['descripcion'],
                    'codigo' => $cat['codigo'],
                    'activo' => true
                ]
            );
        }
    }
}
