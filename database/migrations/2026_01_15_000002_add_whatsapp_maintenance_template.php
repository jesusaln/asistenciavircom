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
        if (!Schema::hasColumn('empresas', 'whatsapp_template_maintenance')) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('whatsapp_template_maintenance')->nullable()->after('whatsapp_template_payment_reminder');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('whatsapp_template_maintenance');
        });
    }
};
