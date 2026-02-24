<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencia_registros', function (Blueprint $table) {
            $table->boolean('face_verified')->default(false)->after('consentimiento_biometrico');
            $table->decimal('face_match_score', 5, 4)->nullable()->after('face_verified');
            $table->decimal('face_liveness_score', 5, 4)->nullable()->after('face_match_score');
            $table->string('face_verification_status', 30)->default('pending')->after('face_liveness_score');
            $table->string('face_provider', 50)->nullable()->after('face_verification_status');
            $table->text('face_verification_notes')->nullable()->after('face_provider');

            $table->index(['empresa_id', 'face_verification_status'], 'asistencia_face_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('asistencia_registros', function (Blueprint $table) {
            $table->dropIndex('asistencia_face_status_idx');
            $table->dropColumn([
                'face_verified',
                'face_match_score',
                'face_liveness_score',
                'face_verification_status',
                'face_provider',
                'face_verification_notes',
            ]);
        });
    }
};

