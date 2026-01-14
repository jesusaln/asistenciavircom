<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\UnidadMedida;
use App\Models\Almacen;
use App\Models\Categoria;
use App\Models\Marca;

class SystemBaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUnidades();
        $this->seedCatalogosBasicos();
    }

    private function seedUnidades(): void
    {
        $unidades = [
            ['nombre' => 'Pieza', 'abreviatura' => 'pz', 'descripcion' => 'Unidad individual'],
            ['nombre' => 'Servicio', 'abreviatura' => 'srv', 'descripcion' => 'Unidad de servicio'],
        ];

        foreach ($unidades as $u) {
            UnidadMedida::updateOrCreate(['nombre' => $u['nombre']], $u);
        }
        $this->command->info('✓ Unidades de Medida base');
    }

    private function seedCatalogosBasicos(): void
    {
        Categoria::firstOrCreate(['nombre' => 'General']);
        Marca::firstOrCreate(['nombre' => 'Genérica']);
        $this->command->info('✓ Categorías y Marcas base');
    }
}
