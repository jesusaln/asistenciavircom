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
        Schema::table('newsletter_tracks', function (Blueprint $table) {
            $table->timestamp('interes_at')->nullable()->after('clic_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('newsletter_tracks', function (Blueprint $table) {
            //
        });
    }
};
