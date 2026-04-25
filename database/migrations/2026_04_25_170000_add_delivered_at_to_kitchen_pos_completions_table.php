<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kitchen_pos_completions', function (Blueprint $table) {
            if (!Schema::hasColumn('kitchen_pos_completions', 'delivered_at')) {
                $table->timestamp('delivered_at')->nullable()->after('completed_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kitchen_pos_completions', function (Blueprint $table) {
            if (Schema::hasColumn('kitchen_pos_completions', 'delivered_at')) {
                $table->dropColumn('delivered_at');
            }
        });
    }
};
