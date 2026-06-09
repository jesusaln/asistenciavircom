<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('face_reference_path')->nullable()->after('checkin_token');
            $table->timestamp('face_enrolled_at')->nullable()->after('face_reference_path');
            $table->timestamp('face_last_verified_at')->nullable()->after('face_enrolled_at');
            $table->string('face_provider', 50)->nullable()->after('face_last_verified_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'face_reference_path',
                'face_enrolled_at',
                'face_last_verified_at',
                'face_provider',
            ]);
        });
    }
};

