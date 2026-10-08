<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kds_events', function (Blueprint $table) {
            $table->id();
            $table->string('event', 32)->index(); // undo_qr | uncomplete | unserve
            $table->string('group_key', 64)->nullable()->index();
            $table->string('check_number', 64)->nullable();
            $table->string('table_no', 32)->nullable();
            $table->unsignedInteger('rvc_id')->nullable();
            $table->longText('removed_json')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kds_events');
    }
};
