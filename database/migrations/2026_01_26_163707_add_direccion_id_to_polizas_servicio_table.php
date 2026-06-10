<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->unsignedBigInteger('direccion_id')->nullable()->after('cliente_id');
            // Assuming 'cliente_direcciones' is the table name for ClienteDireccion model
            // If the table name is different, adjust accordingly.
            // Often it's better to not enforce foreign key constraint if not strictly needed or if table name is unsure
            // But for integrity, it's good. I'll add the column only for now to be safe and avoid errors if table doesn't exist.
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('polizas_servicio', function (Blueprint $table) {
            $table->dropColumn('direccion_id');
        });
    }
};
