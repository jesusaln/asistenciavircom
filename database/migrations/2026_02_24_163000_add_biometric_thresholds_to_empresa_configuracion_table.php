<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->boolean('biometrics_strict_match')->default(false)->after('ticket_default_assignee_id');
            $table->decimal('biometrics_local_match_threshold', 5, 4)->default(0.7200)->after('biometrics_strict_match');
            $table->decimal('biometrics_local_liveness_threshold', 5, 4)->default(0.4500)->after('biometrics_local_match_threshold');
            $table->unsignedInteger('biometrics_geofence_soft_margin_meters')->default(120)->after('biometrics_local_liveness_threshold');
            $table->decimal('biometrics_nearby_match_relax', 5, 4)->default(0.0600)->after('biometrics_geofence_soft_margin_meters');
            $table->decimal('biometrics_nearby_liveness_relax', 5, 4)->default(0.1000)->after('biometrics_nearby_match_relax');
            $table->decimal('biometrics_far_match_penalty', 5, 4)->default(0.0600)->after('biometrics_nearby_liveness_relax');
            $table->decimal('biometrics_far_liveness_penalty', 5, 4)->default(0.1000)->after('biometrics_far_match_penalty');
        });
    }

    public function down(): void
    {
        Schema::table('empresa_configuracion', function (Blueprint $table) {
            $table->dropColumn([
                'biometrics_strict_match',
                'biometrics_local_match_threshold',
                'biometrics_local_liveness_threshold',
                'biometrics_geofence_soft_margin_meters',
                'biometrics_nearby_match_relax',
                'biometrics_nearby_liveness_relax',
                'biometrics_far_match_penalty',
                'biometrics_far_liveness_penalty',
            ]);
        });
    }
};

