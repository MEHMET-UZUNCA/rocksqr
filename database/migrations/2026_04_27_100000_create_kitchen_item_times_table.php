<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_item_times', function (Blueprint $table) {
            $table->string('unit_id', 80)->primary();
            $table->string('check_number', 30)->nullable()->index();
            $table->timestamp('first_seen_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_item_times');
    }
};
