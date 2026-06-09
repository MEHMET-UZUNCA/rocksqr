<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kitchen_pos_completions', function (Blueprint $table) {
            $table->timestamp('first_seen_at')->nullable()->after('completed_at');
            $table->unsignedInteger('prep_seconds')->nullable()->after('first_seen_at');
        });
    }

    public function down(): void
    {
        Schema::table('kitchen_pos_completions', function (Blueprint $table) {
            $table->dropColumn(['first_seen_at', 'prep_seconds']);
        });
    }
};
