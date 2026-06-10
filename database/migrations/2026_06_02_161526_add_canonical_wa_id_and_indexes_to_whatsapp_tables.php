<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Services\WhatsAppService;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('whats_app_chats', 'canonical_wa_id')) {
            Schema::table('whats_app_chats', function (Blueprint $table) {
                $table->string('canonical_wa_id', 20)->nullable()->after('wa_id');
                $table->index(['empresa_id', 'canonical_wa_id', 'created_at'], 'idx_chats_canonical');
            });
        }

        if (!Schema::hasColumn('whats_app_conversations', 'canonical_wa_id')) {
            Schema::table('whats_app_conversations', function (Blueprint $table) {
                $table->string('canonical_wa_id', 20)->nullable()->after('wa_id');
                $table->index(['empresa_id', 'canonical_wa_id'], 'idx_conv_canonical');
            });
        }

        if (!Schema::hasColumn('empresas', 'timezone')) {
            Schema::table('empresas', function (Blueprint $table) {
                $table->string('timezone', 64)->nullable()->after('whatsapp_chatbot_type');
            });
        }

        if (!Schema::hasIndex('whats_app_chats', 'idx_chats_wa_id')) {
            Schema::table('whats_app_chats', function (Blueprint $table) {
                $table->index('wa_id', 'idx_chats_wa_id');
            });
        }

        if (!Schema::hasIndex('whats_app_conversations', 'idx_conv_wa_id')) {
            Schema::table('whats_app_conversations', function (Blueprint $table) {
                $table->index('wa_id', 'idx_conv_wa_id');
            });
        }

    }

    public function down(): void
    {
        Schema::table('whats_app_chats', function (Blueprint $table) {
            $table->dropIndex('idx_chats_canonical');
            $table->dropIndex('idx_chats_wa_id');
            $table->dropColumn('canonical_wa_id');
        });

        Schema::table('whats_app_conversations', function (Blueprint $table) {
            $table->dropIndex('idx_conv_canonical');
            $table->dropIndex('idx_conv_wa_id');
            $table->dropColumn('canonical_wa_id');
        });

        Schema::table('empresas', function (Blueprint $table) {
            $table->dropColumn('timezone');
        });
    }
};
