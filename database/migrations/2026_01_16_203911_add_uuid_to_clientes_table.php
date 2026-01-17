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
        Schema::table('clientes', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
        });

        // Populate existing clients
        \Illuminate\Support\Facades\DB::table('clientes')->orderBy('id')->chunk(100, function ($clientes) {
            foreach ($clientes as $cliente) {
                if (empty($cliente->uuid)) {
                    \Illuminate\Support\Facades\DB::table('clientes')
                        ->where('id', $cliente->id)
                        ->update(['uuid' => \Illuminate\Support\Str::uuid()]);
                }
            }
        });

        Schema::table('clientes', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->unique()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('uuid');
        });
    }
};
