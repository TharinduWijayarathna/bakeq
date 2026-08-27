<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('orders', 'ipg_checkout_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['ipg_checkout_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('ipg_checkout_id', 'stripe_checkout_id');
            $table->renameColumn('ipg_payment_id', 'stripe_payment_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('stripe_checkout_id');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('orders', 'stripe_checkout_id') || Schema::hasColumn('orders', 'ipg_checkout_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['stripe_checkout_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('stripe_checkout_id', 'ipg_checkout_id');
            $table->renameColumn('stripe_payment_id', 'ipg_payment_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('ipg_checkout_id');
        });
    }
};
