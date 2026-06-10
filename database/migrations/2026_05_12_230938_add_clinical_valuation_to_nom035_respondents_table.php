<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('nom035_respondents', function (Blueprint $table) {
            $table->string('clinical_valuation_status')->default('not_required'); // not_required, pending, referred, completed
            $table->text('clinical_valuation_notes')->nullable();
            $table->date('clinical_valuation_date')->nullable();
            $table->string('clinical_valuation_evidence')->nullable(); // Path to PDF/Image
        });
    }

    public function down(): void
    {
        Schema::table('nom035_respondents', function (Blueprint $table) {
            $table->dropColumn(['clinical_valuation_status', 'clinical_valuation_notes', 'clinical_valuation_date', 'clinical_valuation_evidence']);
        });
    }
};
