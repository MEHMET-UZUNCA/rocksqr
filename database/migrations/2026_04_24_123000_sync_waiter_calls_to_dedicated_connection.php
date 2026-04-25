<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $defaultConnection = config('database.default');
        $waiterConnection = config('database.waiter_calls_connection', $defaultConnection);

        if (!Schema::connection($waiterConnection)->hasTable('waiter_calls')) {
            Schema::connection($waiterConnection)->create('waiter_calls', function (Blueprint $table) {
                $table->id();
                $table->integer('table_no')->nullable();
                $table->enum('status', ['pending', 'attended'])->default('pending');
                $table->text('note')->nullable();
                $table->timestamp('attended_at')->nullable();
                $table->timestamps();
            });
        }

        if ($waiterConnection === $defaultConnection) {
            return;
        }

        if (!Schema::connection($defaultConnection)->hasTable('waiter_calls')) {
            return;
        }

        $targetHasRows = DB::connection($waiterConnection)->table('waiter_calls')->count() > 0;
        if ($targetHasRows) {
            return;
        }

        $sourceRows = DB::connection($defaultConnection)->table('waiter_calls')->get();
        if ($sourceRows->isEmpty()) {
            return;
        }

        DB::connection($waiterConnection)->table('waiter_calls')->insert(
            $sourceRows->map(function ($row) {
                return [
                    'id' => $row->id,
                    'table_no' => $row->table_no,
                    'status' => $row->status,
                    'note' => $row->note,
                    'attended_at' => $row->attended_at,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ];
            })->all()
        );
    }

    public function down(): void
    {
        $waiterConnection = config('database.waiter_calls_connection', config('database.default'));

        if (Schema::connection($waiterConnection)->hasTable('waiter_calls')) {
            Schema::connection($waiterConnection)->drop('waiter_calls');
        }
    }
};
