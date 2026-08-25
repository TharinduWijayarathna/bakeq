<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->unsignedInteger('monthly_revenue_budget')->default(0)->after('labor_overhead_percent');
        });
    }

    public function down(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->dropColumn('monthly_revenue_budget');
        });
    }
};
