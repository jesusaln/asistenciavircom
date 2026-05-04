<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('whatsapp_messages')) {
            Schema::create('whatsapp_messages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('empresa_id')->constrained('empresas')->cascadeOnDelete();
                $table->foreignUuid('marketing_campana_id')->nullable()->constrained('marketing_campanas')->nullOnDelete();
                $table->foreignId('marketing_destinatario_id')->nullable()->constrained('marketing_destinatarios')->nullOnDelete();
                $table->string('to', 32);
                $table->string('template_name')->nullable();
                $table->json('template_params')->nullable();
                $table->string('message_id')->nullable()->index();
                $table->string('status', 32)->default('queued')->index();
                $table->json('response')->nullable();
                $table->string('error_code')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (!Schema::hasColumn('whatsapp_messages', 'marketing_campana_id')) {
                $table->foreignUuid('marketing_campana_id')->nullable()->after('empresa_id')->constrained('marketing_campanas')->nullOnDelete();
            }
            if (!Schema::hasColumn('whatsapp_messages', 'marketing_destinatario_id')) {
                $table->foreignId('marketing_destinatario_id')->nullable()->after('marketing_campana_id')->constrained('marketing_destinatarios')->nullOnDelete();
            }
            if (!Schema::hasColumn('whatsapp_messages', 'template_name')) {
                $table->string('template_name')->nullable()->after('to');
            }
            if (!Schema::hasColumn('whatsapp_messages', 'template_params')) {
                $table->json('template_params')->nullable()->after('template_name');
            }
            if (!Schema::hasColumn('whatsapp_messages', 'message_id')) {
                $table->string('message_id')->nullable()->after('template_params');
            }
            if (!Schema::hasColumn('whatsapp_messages', 'status')) {
                $table->string('status', 32)->default('queued')->after('message_id');
            }
            if (!Schema::hasColumn('whatsapp_messages', 'response')) {
                $table->json('response')->nullable()->after('status');
            }
            if (!Schema::hasColumn('whatsapp_messages', 'error_code')) {
                $table->string('error_code')->nullable()->after('response');
            }
        });
    }

    public function down(): void
    {
        Schema::table('whatsapp_messages', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_messages', 'marketing_destinatario_id')) {
                $table->dropConstrainedForeignId('marketing_destinatario_id');
            }
            if (Schema::hasColumn('whatsapp_messages', 'marketing_campana_id')) {
                $table->dropConstrainedForeignId('marketing_campana_id');
            }
        });
    }
};
