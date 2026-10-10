<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('surname', 100)->nullable()->after('name');
            $table->string('role', 20)->default('personel')->after('password');
            $table->boolean('is_active')->default(true)->after('role');
        });

        // Mevcut kurulum sahipleri yönetici kalsın (yeni kayıtlar varsayılan personel)
        DB::table('users')->update(['role' => 'admin']);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['surname', 'role', 'is_active']);
        });
    }
};
