<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // El error indica que polizas_servicio busca empresa_id 8 en la tabla empresa_configuracion.
        // Actualmente empresa_configuracion solo tiene una fila con ID 1.
        // Vamos a actualizar ese ID a 8 para que coincida con el ID de la única empresa que existe.

        $empresa = DB::table('empresas')->first();

        if ($empresa) {
            DB::table('empresa_configuracion')
                ->where('id', 1)
                ->update(['id' => $empresa->id]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No es necesario un rollback destructivo aquí, ya que solo estamos alineando IDs
    }
};
