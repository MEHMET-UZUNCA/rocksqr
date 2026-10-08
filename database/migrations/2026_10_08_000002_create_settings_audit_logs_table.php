<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('user_email', 190)->nullable();
            $table->string('key_name', 100)->index();
            $table->mediumText('old_value')->nullable();
            $table->mediumText('new_value')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings_audit_logs');
    }
};
