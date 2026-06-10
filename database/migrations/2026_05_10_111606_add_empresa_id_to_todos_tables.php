<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tables = ['todos', 'todo_steps', 'todo_attachments'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && !Schema::hasColumn($tableName, 'empresa_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->unsignedBigInteger('empresa_id')->nullable()->default(8)->after('id');
                    $table->index('empresa_id');
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = ['todos', 'todo_steps', 'todo_attachments'];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName) && Schema::hasColumn($tableName, 'empresa_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->dropIndex(['empresa_id']);
                    $table->dropColumn('empresa_id');
                });
            }
        }
    }
};
