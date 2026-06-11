<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('empresa_configuracion', 'meli_active')) {
            Schema::table('empresa_configuracion', function (Blueprint $table) {
                $table->boolean('meli_active')->default(false);
                $table->string('meli_app_id')->nullable();
                $table->string('meli_client_secret')->nullable();
                $table->string('meli_access_token')->nullable();
                $table->string('meli_refresh_token')->nullable();
                $table->bigInteger('meli_user_id')->nullable();
                $table->timestamp('meli_token_expires_at')->nullable();
                $table->string('meli_site_id')->default('MLM');
            });
        }
    }

    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropColumn([
                'meli_active', 'meli_app_id', 'meli_client_secret',
                'meli_access_token', 'meli_refresh_token', 'meli_user_id',
                'meli_token_expires_at', 'meli_site_id',
            ]);
        });
    }
};
