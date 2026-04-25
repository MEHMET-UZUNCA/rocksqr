<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kitchen_pos_completions', function (Blueprint $table) {
            if (!Schema::hasColumn('kitchen_pos_completions', 'name')) {
                $table->string('name', 255)->nullable()->after('table_no');
            }
            if (!Schema::hasColumn('kitchen_pos_completions', 'note')) {
                $table->string('note', 255)->nullable()->after('name');
            }
            if (!Schema::hasColumn('kitchen_pos_completions', 'qty')) {
                $table->unsignedSmallInteger('qty')->default(1)->after('note');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kitchen_pos_completions', function (Blueprint $table) {
            foreach (['name', 'note', 'qty'] as $col) {
                if (Schema::hasColumn('kitchen_pos_completions', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
