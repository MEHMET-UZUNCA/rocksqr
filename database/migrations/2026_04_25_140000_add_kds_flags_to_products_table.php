<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'show_in_kitchen')) {
                $table->boolean('show_in_kitchen')->default(true)->after('is_available');
            }
            if (!Schema::hasColumn('products', 'show_in_bar')) {
                $table->boolean('show_in_bar')->default(false)->after('show_in_kitchen');
            }
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'show_in_bar')) {
                $table->dropColumn('show_in_bar');
            }
            if (Schema::hasColumn('products', 'show_in_kitchen')) {
                $table->dropColumn('show_in_kitchen');
            }
        });
    }
};
