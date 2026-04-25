<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('bar_status', 20)->default('new')->after('status');
            $table->string('kitchen_status', 20)->default('waiting')->after('bar_status');
            $table->timestamp('bar_approved_at')->nullable()->after('items_json');
            $table->timestamp('kitchen_started_at')->nullable()->after('bar_approved_at');
            $table->timestamp('kitchen_ready_at')->nullable()->after('kitchen_started_at');
        });

        DB::table('orders')->where('status', 'new')->update([
            'bar_status' => 'new',
            'kitchen_status' => 'waiting',
        ]);

        DB::table('orders')->where('status', 'preparing')->update([
            'bar_status' => 'approved',
            'kitchen_status' => 'preparing',
            'bar_approved_at' => DB::raw('created_at'),
            'kitchen_started_at' => DB::raw('updated_at'),
        ]);

        DB::table('orders')->where('status', 'ready')->update([
            'bar_status' => 'approved',
            'kitchen_status' => 'ready',
            'bar_approved_at' => DB::raw('created_at'),
            'kitchen_started_at' => DB::raw('created_at'),
            'kitchen_ready_at' => DB::raw('updated_at'),
        ]);

        DB::table('orders')->where('status', 'completed')->update([
            'bar_status' => 'approved',
            'kitchen_status' => 'completed',
            'bar_approved_at' => DB::raw('created_at'),
            'kitchen_started_at' => DB::raw('created_at'),
            'kitchen_ready_at' => DB::raw('updated_at'),
        ]);
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'bar_status',
                'kitchen_status',
                'bar_approved_at',
                'kitchen_started_at',
                'kitchen_ready_at',
            ]);
        });
    }
};
