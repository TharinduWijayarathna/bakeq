<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingredients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit')->default('g');
            $table->decimal('current_stock', 12, 3)->default(0);
            $table->unsignedInteger('unit_cost')->default(0);
            $table->string('supplier')->nullable();
            $table->decimal('reorder_threshold', 12, 3)->default(0);
            $table->date('expiry_date')->nullable()->index();
            $table->timestamps();

            $table->index('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingredients');
    }
};
