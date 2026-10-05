<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('table_no')->nullable()->change();
        });

        $connection = config('database.waiter_calls_connection', config('database.default'));

        Schema::connection($connection)->table('waiter_calls', function (Blueprint $table) {
            $table->integer('table_no')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->integer('table_no')->nullable(false)->change();
        });

        $connection = config('database.waiter_calls_connection', config('database.default'));

        Schema::connection($connection)->table('waiter_calls', function (Blueprint $table) {
            $table->integer('table_no')->nullable(false)->change();
        });
    }
};
