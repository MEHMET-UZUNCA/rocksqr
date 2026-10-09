<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $connection = config('database.waiter_calls_connection', config('database.default'));

        if (!Schema::connection($connection)->hasColumn('waiter_calls', 'auto_closed_at')) {
            Schema::connection($connection)->table('waiter_calls', function (Blueprint $table) {
                $table->timestamp('auto_closed_at')->nullable()->after('attended_at');
            });
        }
    }

    public function down(): void
    {
        $connection = config('database.waiter_calls_connection', config('database.default'));

        if (Schema::connection($connection)->hasColumn('waiter_calls', 'auto_closed_at')) {
            Schema::connection($connection)->table('waiter_calls', function (Blueprint $table) {
                $table->dropColumn('auto_closed_at');
            });
        }
    }
};
