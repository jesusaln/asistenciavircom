<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencia_registros', function (Blueprint $table) {
            $table->unsignedTinyInteger('face_detected_count')->nullable()->after('face_verification_notes');
            $table->boolean('face_capture_quality_passed')->default(false)->after('face_detected_count');
            $table->decimal('face_quality_brightness', 5, 4)->nullable()->after('face_capture_quality_passed');
            $table->decimal('face_quality_sharpness', 5, 4)->nullable()->after('face_quality_brightness');
            $table->decimal('face_quality_area_ratio', 5, 4)->nullable()->after('face_quality_sharpness');
            $table->decimal('face_quality_center_offset', 5, 4)->nullable()->after('face_quality_area_ratio');
            $table->string('face_quality_message', 255)->nullable()->after('face_quality_center_offset');
        });
    }

    public function down(): void
    {
        Schema::table('asistencia_registros', function (Blueprint $table) {
            $table->dropColumn([
                'face_detected_count',
                'face_capture_quality_passed',
                'face_quality_brightness',
                'face_quality_sharpness',
                'face_quality_area_ratio',
                'face_quality_center_offset',
                'face_quality_message',
            ]);
        });
    }
};

