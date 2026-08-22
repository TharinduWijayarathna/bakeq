<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('designer_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('min_tiers')->default(1);
            $table->unsignedTinyInteger('max_tiers')->default(3);
            $table->unsignedTinyInteger('lead_days')->default(3);
            $table->text('notice')->nullable();
            $table->unsignedInteger('base_price')->default(450000);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('designer_settings');
    }
};
