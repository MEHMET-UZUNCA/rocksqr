<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mutfak tamamlama çipi içeriği (ürün adları + açıklama notları).
        // Feed kapansa / F5 atılsa da alt şeritteki çip içeriği kalıcı kalır.
        Schema::table('kitchen_pos_completions', function (Blueprint $table) {
            $table->text('items_list')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('kitchen_pos_completions', function (Blueprint $table) {
            $table->dropColumn('items_list');
        });
    }
};
