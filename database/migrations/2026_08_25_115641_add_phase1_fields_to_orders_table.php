<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('order_source')->default('online')->index()->after('user_id');
            $table->string('origin')->default('catalog')->index()->after('order_source');
            $table->string('fulfillment_method')->default('delivery')->after('origin');
            $table->unsignedInteger('addons_total')->default(0)->after('subtotal');
            $table->unsignedInteger('delivery_fee')->default(0)->after('addons_total');
            $table->unsignedInteger('tax_amount')->default(0)->after('delivery_fee');
            $table->unsignedInteger('deposit_paid')->default(0)->after('tax_amount');
            $table->unsignedInteger('total_due')->default(0)->after('deposit_paid');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'order_source',
                'origin',
                'fulfillment_method',
                'addons_total',
                'delivery_fee',
                'tax_amount',
                'deposit_paid',
                'total_due',
            ]);
        });
    }
};
