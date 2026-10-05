<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('database.waiter_calls_connection', config('database.default'));

        Schema::connection($connection)->table('waiter_calls', function (Blueprint $table) use ($connection) {
            if (!Schema::connection($connection)->hasColumn('waiter_calls', 'room_no')) {
                $table->string('room_no', 16)->nullable()->after('table_no');
            }
        });
    }

    public function down(): void
    {
        $connection = config('database.waiter_calls_connection', config('database.default'));

        Schema::connection($connection)->table('waiter_calls', function (Blueprint $table) use ($connection) {
            if (Schema::connection($connection)->hasColumn('waiter_calls', 'room_no')) {
                $table->dropColumn('room_no');
            }
        });
    }
};
