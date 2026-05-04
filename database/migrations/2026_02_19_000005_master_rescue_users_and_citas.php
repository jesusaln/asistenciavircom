<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Rescue USERS table
        Schema::table('users', function (Blueprint $table) {
            $cols = [
                'telefono' => ['string', null],
                'fecha_nacimiento' => ['date', null],
                'curp' => ['string', null],
                'rfc' => ['string', null],
                'direccion' => ['text', null],
                'nss' => ['string', null],
                'puesto' => ['string', null],
                'departamento' => ['string', null],
                'fecha_contratacion' => ['date', null],
                'salario' => ['decimal', 15, 2, 0],
                'tipo_contrato' => ['string', null],
                'numero_empleado' => ['string', null],
                'contacto_emergencia_nombre' => ['string', null],
                'contacto_emergencia_telefono' => ['string', null],
                'contacto_emergencia_parentesco' => ['string', null],
                'banco' => ['string', null],
                'numero_cuenta' => ['string', null],
                'clabe_interbancaria' => ['string', null],
                'observaciones' => ['text', null],
                'es_empleado' => ['boolean', false],
                'almacen_venta_id' => ['unsignedBigInteger', null],
                'almacen_compra_id' => ['unsignedBigInteger', null],
                'es_tecnico' => ['boolean', false],
                'es_vendedor' => ['boolean', false],
                'margen_venta_productos' => ['decimal', 8, 2, 0],
                'margen_venta_servicios' => ['decimal', 8, 2, 0],
                'comision_instalacion' => ['decimal', 15, 2, 0],
            ];

            foreach ($cols as $col => $def) {
                if (!Schema::hasColumn('users', $col)) {
                    $type = $def[0];
                    if ($type === 'string')
                        $table->string($col)->nullable();
                    elseif ($type === 'text')
                        $table->text($col)->nullable();
                    elseif ($type === 'boolean')
                        $table->boolean($col)->default($def[1]);
                    elseif ($type === 'date')
                        $table->date($col)->nullable();
                    elseif ($type === 'decimal')
                        $table->decimal($col, $def[1], $def[2])->default($def[3]);
                    elseif ($type === 'unsignedBigInteger')
                        $table->unsignedBigInteger($col)->nullable();
                }
            }
        });

        // 2. Rescue CITAS table
        Schema::table('citas', function (Blueprint $table) {
            // Make legacy columns nullable if they exist
            if (Schema::hasColumn('citas', 'titulo')) {
                $table->string('titulo')->nullable()->change();
            }
            if (Schema::hasColumn('citas', 'fecha_inicio')) {
                $table->dateTime('fecha_inicio')->nullable()->change();
            }
            if (Schema::hasColumn('citas', 'fecha_fin')) {
                $table->dateTime('fecha_fin')->nullable()->change();
            }

            $cols = [
                'folio' => ['string', null],
                'empresa_id' => ['unsignedBigInteger', null],
                'tipo_servicio' => ['string', null],
                'fecha_hora' => ['dateTime', null],
                'descripcion' => ['text', null],
                'problema_reportado' => ['text', null],
                'prioridad' => ['string', 'media'],
                'evidencias' => ['json', null],
                'foto_equipo' => ['string', null],
                'foto_hoja_servicio' => ['string', null],
                'foto_identificacion' => ['string', null],
                'tipo_equipo' => ['string', null],
                'marca_equipo' => ['string', null],
                'modelo_equipo' => ['string', null],
                'subtotal' => ['decimal', 15, 2, 0],
                'descuento_general' => ['decimal', 15, 2, 0],
                'descuento_items' => ['decimal', 15, 2, 0],
                'iva' => ['decimal', 15, 2, 0],
                'total' => ['decimal', 15, 2, 0],
                'notas' => ['text', null],
                'inicio_servicio' => ['dateTime', null],
                'fin_servicio' => ['dateTime', null],
                'tiempo_servicio' => ['integer', null],
            ];

            foreach ($cols as $col => $def) {
                if (!Schema::hasColumn('citas', $col)) {
                    $type = $def[0];
                    if ($type === 'string')
                        $table->string($col)->nullable();
                    elseif ($type === 'text')
                        $table->text($col)->nullable();
                    elseif ($type === 'boolean')
                        $table->boolean($col)->default($def[1]);
                    elseif ($type === 'dateTime')
                        $table->dateTime($col)->nullable();
                    elseif ($type === 'decimal')
                        $table->decimal($col, $def[1], $def[2])->default($def[3]);
                    elseif ($type === 'unsignedBigInteger')
                        $table->unsignedBigInteger($col)->nullable();
                    elseif ($type === 'integer')
                        $table->integer($col)->nullable();
                    elseif ($type === 'json')
                        $table->json($col)->nullable();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
