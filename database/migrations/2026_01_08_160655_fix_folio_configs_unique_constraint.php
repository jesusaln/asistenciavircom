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
        Schema::table('folio_configs', function (Blueprint $table) {
            // Drop the global unique constraint that prevents multiple companies from having the same document_type
            $table->dropUnique('folio_configs_document_type_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('folio_configs', function (Blueprint $table) {
            // Try to restore it, though this might fail if there are duplicates
            $table->unique('document_type');
        });
    }
};
