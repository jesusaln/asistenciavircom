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
        Schema::table('todos', function (Blueprint $table) {
            if (!Schema::hasColumn('todos', 'is_my_day')) {
                $table->boolean('is_my_day')->default(false)->after('status');
            }
            if (!Schema::hasColumn('todos', 'reminder_at')) {
                $table->dateTime('reminder_at')->nullable()->after('due_date');
            }
            if (!Schema::hasColumn('todos', 'recurrence')) {
                $table->enum('recurrence', ['none', 'daily', 'weekly', 'monthly', 'yearly'])->default('none')->after('reminder_at');
            }
            if (!Schema::hasColumn('todos', 'related_type')) {
                $table->nullableMorphs('related'); // Para vincular a Citas, Tickets, etc.
            }
            if (!Schema::hasColumn('todos', 'notes')) {
                $table->text('notes')->nullable()->after('description');
            }
        });

        if (!Schema::hasTable('todo_steps')) {
            Schema::create('todo_steps', function (Blueprint $table) {
                $table->id();
                $table->foreignId('todo_id')->constrained()->onDelete('cascade');
                $table->string('title');
                $table->boolean('is_completed')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('todo_steps');
        Schema::table('todos', function (Blueprint $table) {
            $cols = [];
            foreach (['is_my_day', 'reminder_at', 'recurrence', 'notes', 'related_type', 'related_id'] as $col) {
                if (Schema::hasColumn('todos', $col)) {
                    $cols[] = $col;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
