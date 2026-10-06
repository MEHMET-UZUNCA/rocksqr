<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kitchen_item_logs', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->unsignedInteger('qty')->default(1);
            $table->string('source', 4)->default('sym'); // sym | qr
            $table->string('table_no', 32)->nullable();
            $table->string('room_no', 16)->nullable();
            $table->string('check_number', 64)->nullable();
            $table->string('group_key', 64)->index();
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('completed_at')->index();
            $table->unsignedInteger('prep_seconds')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kitchen_item_logs');
    }
};
