<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('production_status')->default('planning')->index()->after('status');
            $table->string('payment_method')->nullable()->after('notes');
            $table->string('discount_type')->nullable()->after('payment_method');
            $table->unsignedInteger('discount_value')->default(0)->after('discount_type');
            $table->unsignedInteger('discount_amount')->default(0)->after('discount_value');
            $table->string('receipt_number')->nullable()->unique()->after('discount_amount');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'production_status',
                'payment_method',
                'discount_type',
                'discount_value',
                'discount_amount',
                'receipt_number',
            ]);
        });
    }
};
