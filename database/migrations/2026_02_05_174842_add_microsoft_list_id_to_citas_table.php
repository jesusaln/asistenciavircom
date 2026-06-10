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
        if (!Schema::hasColumn('citas', 'microsoft_list_id')) {
            Schema::table('citas', function (Blueprint $table) {
                $table->string('microsoft_list_id')->nullable()->after('microsoft_task_id')->index();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('citas', function (Blueprint $table) {
            $table->dropColumn('microsoft_list_id');
        });
    }
};
