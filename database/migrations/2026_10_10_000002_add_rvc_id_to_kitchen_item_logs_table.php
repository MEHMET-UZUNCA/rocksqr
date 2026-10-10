<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kitchen_item_logs', function (Blueprint $table) {
            $table->unsignedInteger('rvc_id')->default(0)->after('source');
            $table->index('rvc_id');
        });
    }

    public function down(): void
    {
        Schema::table('kitchen_item_logs', function (Blueprint $table) {
            $table->dropIndex(['rvc_id']);
            $table->dropColumn('rvc_id');
        });
    }
};
