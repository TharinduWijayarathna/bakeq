<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_status')->default('unpaid')->after('payment_method');
            $table->unsignedInteger('payment_amount')->default(0)->after('payment_status');
            $table->string('stripe_checkout_id')->nullable()->after('payment_amount');
            $table->string('stripe_payment_id')->nullable()->after('stripe_checkout_id');
            $table->timestamp('paid_at')->nullable()->after('stripe_payment_id');

            $table->index('payment_status');
            $table->index('stripe_checkout_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['payment_status']);
            $table->dropIndex(['stripe_checkout_id']);
            $table->dropColumn([
                'payment_status',
                'payment_amount',
                'stripe_checkout_id',
                'stripe_payment_id',
                'paid_at',
            ]);
        });
    }
};
