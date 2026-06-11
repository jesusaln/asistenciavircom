<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Producto;
use App\Services\CVAService;

class MeliFixImages extends Command
{
    protected $signature = 'cva:fix-images';
    protected $description = 'Actualizar imágenes de productos CVA a alta resolución';

    public function handle(CVAService $cva)
    {
        $this->info('Buscando productos CVA con imágenes mejorables...');

        $productos = Producto::where('origen', 'CVA')
            ->whereNotNull('cva_clave')
            ->where('imagen', 'like', '%imagen_art_detalles%')
            ->get();

        $bar = $this->output->createProgressBar($productos->count());
        $bar->start();
        $updated = 0;

        foreach ($productos as $producto) {
            try {
                $images = $cva->getHighResImages($producto->cva_clave);

                if (!empty($images)) {
                    $highResUrl = $images[0];
                    if ($highResUrl !== $producto->imagen) {
                        $producto->update(['imagen' => $highResUrl]);
                        $updated++;
                    }
                }
            } catch (\Exception $e) {
                $this->line("  Error con {$producto->codigo}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Actualizados: {$updated} productos");
    }
}
