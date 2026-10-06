<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'auto_closed_at')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->timestamp('auto_closed_at')->nullable()->after('completed_at');
            });
        }
        if (!Schema::hasColumn('kitchen_pos_completions', 'auto_closed_at')) {
            Schema::table('kitchen_pos_completions', function (Blueprint $table) {
                $table->timestamp('auto_closed_at')->nullable()->after('delivered_at');
            });
        }
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('auto_closed_at');
        });
        Schema::table('kitchen_pos_completions', function (Blueprint $table) {
            $table->dropColumn('auto_closed_at');
        });
    }
};
