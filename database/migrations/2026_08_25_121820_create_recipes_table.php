<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recipes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cake_id')->index()->constrained()->cascadeOnDelete();
            $table->string('name')->nullable();
            $table->string('size_label')->default('');
            $table->timestamps();

            $table->unique(['cake_id', 'size_label']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recipes');
    }
};
