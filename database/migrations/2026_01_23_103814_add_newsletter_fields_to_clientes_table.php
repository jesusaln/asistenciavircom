<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->boolean('recibe_newsletter')->default(true)->after('email');
            $table->timestamp('newsletter_unsubscribed_at')->nullable()->after('recibe_newsletter');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn(['recibe_newsletter', 'newsletter_unsubscribed_at']);
        });
    }
};
