<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shop_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('delivery_fee')->default(50000);
            $table->unsignedInteger('pickup_fee')->default(0);
            $table->decimal('tax_percent', 5, 2)->default(0);
            $table->decimal('deposit_percent', 5, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shop_settings');
    }
};
