<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Cuestionarios (Guías I, II, III)
        if (!Schema::hasTable('nom035_questionnaires')) {
            Schema::create('nom035_questionnaires', function (Blueprint $table) {
                $table->id();
                $table->string('guide'); // 'I', 'II', 'III'
                $table->string('name');
                $table->string('version')->nullable();
                $table->timestamps();
            });
        }

        // 2. Preguntas de cada guía
        if (!Schema::hasTable('nom035_questions')) {
            Schema::create('nom035_questions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('questionnaire_id')->constrained('nom035_questionnaires');
                $table->string('section')->nullable(); // 'Sección I', etc.
                $table->integer('order');
                $table->text('question_text');
                $table->string('category')->nullable(); // 'Ambiente de trabajo', etc.
                $table->string('domain')->nullable();   // 'Condiciones en el ambiente...', etc.
                $table->boolean('is_inverse')->default(false);
                $table->boolean('has_options')->default(true);
                $table->timestamps();
            });
        }

        // 3. Periodos de evaluación
        if (!Schema::hasTable('nom035_evaluation_periods')) {
            Schema::create('nom035_evaluation_periods', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas');
                $table->string('name');
                $table->date('start_date');
                $table->date('end_date');
                $table->boolean('is_active')->default(false);
                $table->string('status')->default('pending'); // pending, active, closed
                $table->timestamps();
            });
        }

        // 4. Encuestados (vincula empleados con periodos)
        if (!Schema::hasTable('nom035_respondents')) {
            Schema::create('nom035_respondents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('evaluation_period_id')->constrained('nom035_evaluation_periods');
                $table->foreignId('empleado_id')->constrained('empleados');
                $table->string('guide'); // 'I', 'II', 'III'
                $table->string('status')->default('pending'); // pending, completed
                $table->string('risk_level')->nullable();
                $table->integer('total_score')->nullable();
                $table->json('results')->nullable(); // Resultados detallados
                $table->timestamp('completed_at')->nullable();
                $table->timestamps();
            });
        }

        // 5. Respuestas
        if (!Schema::hasTable('nom035_answers')) {
            Schema::create('nom035_answers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('respondent_id')->constrained('nom035_respondents');
                $table->foreignId('question_id')->constrained('nom035_questions');
                $table->integer('value'); // 0-4 para Likert, 0/1 para binario
                $table->unique(['respondent_id', 'question_id']);
                $table->timestamps();
            });
        }

        // 6. Planes de acción
        if (!Schema::hasTable('nom035_action_tasks')) {
            Schema::create('nom035_action_tasks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas');
                $table->string('domain'); // Dominio de riesgo
                $table->string('risk_level');
                $table->text('recommendation');
                $table->string('status')->default('pending');
                $table->date('due_date')->nullable();
                $table->text('evidence')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('nom035_answers');
        Schema::dropIfExists('nom035_respondents');
        Schema::dropIfExists('nom035_action_tasks');
        Schema::dropIfExists('nom035_evaluation_periods');
        Schema::dropIfExists('nom035_questions');
        Schema::dropIfExists('nom035_questionnaires');
    }
};
