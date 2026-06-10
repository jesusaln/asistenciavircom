<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trading_experience', function (Blueprint $table) {
            if (!Schema::hasColumn('trading_experience', 'atr_percent')) {
                $table->decimal('atr_percent', 12, 8)->nullable()->after('ai_confidence');
            }
            if (!Schema::hasColumn('trading_experience', 'atr_value')) {
                $table->decimal('atr_value', 24, 8)->nullable()->after('atr_percent');
            }
            if (!Schema::hasColumn('trading_experience', 'macro_timeframe')) {
                $table->string('macro_timeframe', 10)->nullable()->after('atr_value');
            }
            if (!Schema::hasColumn('trading_experience', 'macro_trend')) {
                $table->string('macro_trend', 20)->nullable()->after('macro_timeframe');
            }
            if (!Schema::hasColumn('trading_experience', 'stop_loss')) {
                $table->decimal('stop_loss', 24, 8)->nullable()->after('macro_trend');
            }
            if (!Schema::hasColumn('trading_experience', 'trailing_stop')) {
                $table->decimal('trailing_stop', 24, 8)->nullable()->after('stop_loss');
            }
            if (!Schema::hasColumn('trading_experience', 'highest_price')) {
                $table->decimal('highest_price', 24, 8)->nullable()->after('trailing_stop');
            }
        });
    }

    public function down(): void
    {
        Schema::table('trading_experience', function (Blueprint $table) {
            $cols = [];
            foreach ([
                'atr_percent',
                'atr_value',
                'macro_timeframe',
                'macro_trend',
                'stop_loss',
                'trailing_stop',
                'highest_price',
            ] as $col) {
                if (Schema::hasColumn('trading_experience', $col)) {
                    $cols[] = $col;
                }
            }
            if (!empty($cols)) {
                $table->dropColumn($cols);
            }
        });
    }
};
