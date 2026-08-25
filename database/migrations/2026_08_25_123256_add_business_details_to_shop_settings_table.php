<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->string('business_name')->default('Bakeq Cakes')->after('labor_overhead_percent');
            $table->string('business_address')->nullable()->after('business_name');
            $table->string('business_phone')->nullable()->after('business_address');
            $table->string('business_email')->nullable()->after('business_phone');
        });
    }

    public function down(): void
    {
        Schema::table('shop_settings', function (Blueprint $table) {
            $table->dropColumn([
                'business_name',
                'business_address',
                'business_phone',
                'business_email',
            ]);
        });
    }
};
