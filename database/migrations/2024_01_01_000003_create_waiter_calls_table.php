<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $connection = config('database.waiter_calls_connection', config('database.default'));

        Schema::connection($connection)->create('waiter_calls', function (Blueprint $table) {
            $table->id();
            $table->integer('table_no');
            $table->enum('status', ['pending', 'attended'])->default('pending');
            $table->text('note')->nullable();
            $table->timestamp('attended_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        $connection = config('database.waiter_calls_connection', config('database.default'));

        Schema::connection($connection)->dropIfExists('waiter_calls');
    }
};