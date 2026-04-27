<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kitchen_pos_completions', function (Blueprint $table) {
            // Servis edildiğinde aktif olan item fingerprint'lerinin JSON listesi.
            // Sonraki sorguyla karşılaştırarak sadece yeni eklenen ürünleri tespit eder.
            $table->text('served_item_keys')->nullable()->after('completed_at');
        });
    }

    public function down(): void
    {
        Schema::table('kitchen_pos_completions', function (Blueprint $table) {
            $table->dropColumn('served_item_keys');
        });
    }
};
