<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Update model_has_roles table
        if (Schema::hasTable('model_has_roles')) {
            // Delete duplicate roles before updating to prevent unique/primary key violations
            DB::statement("
                DELETE FROM model_has_roles 
                WHERE model_type = 'App\\Models\\User' 
                  AND (role_id, model_id) IN (
                      SELECT role_id, model_id FROM model_has_roles WHERE model_type = 'user'
                  )
            ");
            
            DB::table('model_has_roles')
                ->where('model_type', 'App\\Models\\User')
                ->update(['model_type' => 'user']);
        }

        // 2. Update model_has_permissions table
        if (Schema::hasTable('model_has_permissions')) {
            DB::statement("
                DELETE FROM model_has_permissions 
                WHERE model_type = 'App\\Models\\User' 
                  AND (permission_id, model_id) IN (
                      SELECT permission_id, model_id FROM model_has_permissions WHERE model_type = 'user'
                  )
            ");
            
            DB::table('model_has_permissions')
                ->where('model_type', 'App\\Models\\User')
                ->update(['model_type' => 'user']);
        }

        // 3. Update audits table
        if (Schema::hasTable('audits')) {
            DB::table('audits')
                ->where('user_type', 'App\\Models\\User')
                ->update(['user_type' => 'user']);
            
            DB::table('audits')
                ->where('auditable_type', 'App\\Models\\User')
                ->update(['auditable_type' => 'user']);
        }

        // 4. Update ventas table
        if (Schema::hasTable('ventas')) {
            DB::table('ventas')
                ->where('vendedor_type', 'App\\Models\\User')
                ->update(['vendedor_type' => 'user']);
        }

        // 5. Update notifications table
        if (Schema::hasTable('notifications')) {
            DB::table('notifications')
                ->where('notifiable_type', 'App\\Models\\User')
                ->update(['notifiable_type' => 'user']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('model_has_roles')) {
            DB::table('model_has_roles')
                ->where('model_type', 'user')
                ->update(['model_type' => 'App\\Models\\User']);
        }

        if (Schema::hasTable('model_has_permissions')) {
            DB::table('model_has_permissions')
                ->where('model_type', 'user')
                ->update(['model_type' => 'App\\Models\\User']);
        }

        if (Schema::hasTable('audits')) {
            DB::table('audits')
                ->where('user_type', 'user')
                ->update(['user_type' => 'App\\Models\\User']);
            
            DB::table('audits')
                ->where('auditable_type', 'user')
                ->update(['auditable_type' => 'App\\Models\\User']);
        }

        if (Schema::hasTable('ventas')) {
            DB::table('ventas')
                ->where('vendedor_type', 'user')
                ->update(['vendedor_type' => 'App\\Models\\User']);
        }

        if (Schema::hasTable('notifications')) {
            DB::table('notifications')
                ->where('notifiable_type', 'user')
                ->update(['notifiable_type' => 'App\\Models\\User']);
        }
    }
};
