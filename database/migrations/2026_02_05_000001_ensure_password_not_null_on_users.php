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
        // Prevenir error si la tabla no existe
        if (!Schema::hasTable('users')) {
            return;
        }

        // 1. Identificar usuarios con password nulo y establecer uno temporal seguro
        // Esto evita que la restricción NOT NULL falle
        $affected = DB::table('users')
            ->whereNull('password')
            ->update(['password' => '$2y$12$K.wHq0f.z.u.y.x.w.v.u.t.s.r.q.p.o.n.m.l.k.j.i.h.g.f.e.d.c.b.a']); // Hash dummy de alta seguridad

        if ($affected > 0) {
            \Illuminate\Support\Facades\Log::warning("Se han actualizado $affected usuarios con password nulo a un hash seguro por defecto en la migración.");
        }

        // 2. Hacer la columna NOT NULL
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('password')->nullable()->change();
            });
        }
    }
};
