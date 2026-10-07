<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tamamlamanın ait olduğu Gelir Merkezi (RVC). 0 = deploy öncesi eski kayıt;
        // şerit sorgularında tüm ekranlarda görünür, temizlik saatinde silinir.
        Schema::table('kitchen_pos_completions', function (Blueprint $table) {
            $table->unsignedInteger('rvc_id')->default(0)->after('kind');
        });
    }

    public function down(): void
    {
        Schema::table('kitchen_pos_completions', function (Blueprint $table) {
            $table->dropColumn('rvc_id');
        });
    }
};
