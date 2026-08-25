<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waste_entries', function (Blueprint $table) {
            $table->id();
            $table->date('wasted_on')->index();
            $table->foreignId('ingredient_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->foreignId('cake_id')->nullable()->index()->constrained()->nullOnDelete();
            $table->decimal('quantity', 12, 3);
            $table->string('reason')->index();
            $table->unsignedInteger('cost_impact')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waste_entries');
    }
};
