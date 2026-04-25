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
        Schema::create('kitchen_pos_completions', function (Blueprint $table) {
            $table->id();
            $table->string('group_key', 64)->index(); // CheckNumber or "T{table}" or "M{item_id}"
            $table->string('check_number', 64)->nullable();
            $table->string('table_no', 32)->nullable();
            $table->string('kind', 16)->default('check'); // check | checkless_msg
            $table->timestamp('completed_at')->useCurrent();
            $table->index(['kind', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kitchen_pos_completions');
    }
};
