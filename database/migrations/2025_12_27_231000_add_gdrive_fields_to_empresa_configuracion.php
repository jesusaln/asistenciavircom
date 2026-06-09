<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            if (!Schema::hasColumn('empresa_configuracion', 'gdrive_enabled')) {
                $table->boolean('gdrive_enabled')->default(false);
            }
            if (!Schema::hasColumn('empresa_configuracion', 'gdrive_client_id')) {
                $table->string('gdrive_client_id')->nullable();
            }
            if (!Schema::hasColumn('empresa_configuracion', 'gdrive_client_secret')) {
                $table->string('gdrive_client_secret')->nullable();
            }
            if (!Schema::hasColumn('empresa_configuracion', 'gdrive_access_token')) {
                $table->text('gdrive_access_token')->nullable();
            }
            if (!Schema::hasColumn('empresa_configuracion', 'gdrive_refresh_token')) {
                $table->text('gdrive_refresh_token')->nullable();
            }
            if (!Schema::hasColumn('empresa_configuracion', 'gdrive_folder_id')) {
                $table->string('gdrive_folder_id')->nullable();
            }
            if (!Schema::hasColumn('empresa_configuracion', 'gdrive_folder_name')) {
                $table->string('gdrive_folder_name')->default('CDD_Backups');
            }
            if (!Schema::hasColumn('empresa_configuracion', 'gdrive_auto_backup')) {
                $table->boolean('gdrive_auto_backup')->default(false);
            }
            if (!Schema::hasColumn('empresa_configuracion', 'gdrive_token_expires_at')) {
                $table->timestamp('gdrive_token_expires_at')->nullable();
            }
            if (!Schema::hasColumn('empresa_configuracion', 'gdrive_last_sync')) {
                $table->timestamp('gdrive_last_sync')->nullable();
            }
            if (!Schema::hasColumn('empresa_configuracion', 'cloud_provider')) {
                $table->string('cloud_provider')->default('none');
            }
        });
    }

    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            if (Schema::hasColumn('empresa_configuracion', 'gdrive_enabled')) {
                $table->dropColumn('gdrive_enabled');
            }
            if (Schema::hasColumn('empresa_configuracion', 'gdrive_client_id')) {
                $table->dropColumn('gdrive_client_id');
            }
            if (Schema::hasColumn('empresa_configuracion', 'gdrive_client_secret')) {
                $table->dropColumn('gdrive_client_secret');
            }
            if (Schema::hasColumn('empresa_configuracion', 'gdrive_access_token')) {
                $table->dropColumn('gdrive_access_token');
            }
            if (Schema::hasColumn('empresa_configuracion', 'gdrive_refresh_token')) {
                $table->dropColumn('gdrive_refresh_token');
            }
            if (Schema::hasColumn('empresa_configuracion', 'gdrive_folder_id')) {
                $table->dropColumn('gdrive_folder_id');
            }
            if (Schema::hasColumn('empresa_configuracion', 'gdrive_folder_name')) {
                $table->dropColumn('gdrive_folder_name');
            }
            if (Schema::hasColumn('empresa_configuracion', 'gdrive_auto_backup')) {
                $table->dropColumn('gdrive_auto_backup');
            }
            if (Schema::hasColumn('empresa_configuracion', 'gdrive_token_expires_at')) {
                $table->dropColumn('gdrive_token_expires_at');
            }
            if (Schema::hasColumn('empresa_configuracion', 'gdrive_last_sync')) {
                $table->dropColumn('gdrive_last_sync');
            }
            if (Schema::hasColumn('empresa_configuracion', 'cloud_provider')) {
                $table->dropColumn('cloud_provider');
            }
        });
    }
};
