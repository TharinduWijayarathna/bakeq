<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('number')->unique();
            $table->timestamp('issued_at')->index();
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('discount_amount')->default(0);
            $table->unsignedInteger('delivery_fee')->default(0);
            $table->unsignedInteger('tax_amount')->default(0);
            $table->unsignedInteger('deposit_paid')->default(0);
            $table->unsignedInteger('total_due');
            $table->json('line_items');
            $table->json('business_snapshot')->nullable();
            $table->json('customer_snapshot')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
