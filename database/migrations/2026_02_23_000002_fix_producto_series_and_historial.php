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
        // 1. Corregir producto_series
        if (Schema::hasTable('producto_series')) {
            Schema::table('producto_series', function (Blueprint $table) {
                if (!Schema::hasColumn('producto_series', 'compra_id')) {
                    $table->unsignedBigInteger('compra_id')->nullable()->index();
                }
                if (!Schema::hasColumn('producto_series', 'venta_id')) {
                    $table->unsignedBigInteger('venta_id')->nullable()->index();
                }
                if (!Schema::hasColumn('producto_series', 'cita_id')) {
                    $table->unsignedBigInteger('cita_id')->nullable()->index();
                }
                if (!Schema::hasColumn('producto_series', 'almacen_id')) {
                    $table->unsignedBigInteger('almacen_id')->nullable()->index();
                }
                if (!Schema::hasColumn('producto_series', 'numero_serie')) {
                    if (Schema::hasColumn('producto_series', 'serie')) {
                        $table->renameColumn('serie', 'numero_serie');
                    } else {
                        $table->string('numero_serie')->nullable()->index();
                    }
                }
                if (!Schema::hasColumn('producto_series', 'estado')) {
                    $table->string('estado')->default('en_stock')->index();
                }
                if (!Schema::hasColumn('producto_series', 'deleted_at')) {
                    $table->softDeletes();
                }
            });
        }

        // 2. Corregir producto_precio_historial
        if (Schema::hasTable('producto_precio_historial')) {
            Schema::table('producto_precio_historial', function (Blueprint $table) {
                if (!Schema::hasColumn('producto_precio_historial', 'empresa_id')) {
                    $table->unsignedBigInteger('empresa_id')->nullable()->index();
                }
                if (!Schema::hasColumn('producto_precio_historial', 'precio_compra_anterior')) {
                    if (Schema::hasColumn('producto_precio_historial', 'precio_anterior')) {
                        $table->renameColumn('precio_anterior', 'precio_compra_anterior');
                    } else {
                        $table->decimal('precio_compra_anterior', 15, 2)->default(0);
                    }
                }
                if (!Schema::hasColumn('producto_precio_historial', 'precio_compra_nuevo')) {
                    if (Schema::hasColumn('producto_precio_historial', 'precio_nuevo')) {
                        $table->renameColumn('precio_nuevo', 'precio_compra_nuevo');
                    } else {
                        $table->decimal('precio_compra_nuevo', 15, 2)->default(0);
                    }
                }
                if (!Schema::hasColumn('producto_precio_historial', 'precio_venta_anterior')) {
                    $table->decimal('precio_venta_anterior', 15, 2)->nullable();
                }
                if (!Schema::hasColumn('producto_precio_historial', 'precio_venta_nuevo')) {
                    $table->decimal('precio_venta_nuevo', 15, 2)->nullable();
                }
                if (!Schema::hasColumn('producto_precio_historial', 'tipo_cambio')) {
                    $table->string('tipo_cambio')->nullable();
                }
                if (!Schema::hasColumn('producto_precio_historial', 'notas')) {
                    if (Schema::hasColumn('producto_precio_historial', 'motivo')) {
                        $table->renameColumn('motivo', 'notas');
                    } else {
                        $table->text('notas')->nullable();
                    }
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No destructivo
    }
};
