<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->decimal('labor_overhead_percent', 5, 2)->default(15)->after('deposit_percent');
        });
    }

    public function down(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->dropColumn('labor_overhead_percent');
        });
    }
};
