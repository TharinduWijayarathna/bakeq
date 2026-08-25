<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cakes', function (Blueprint $table) {
            $table->json('size_options')->nullable()->after('serves');
            $table->json('ingredients')->nullable()->after('size_options');
            $table->json('allergens')->nullable()->after('ingredients');
            $table->unsignedSmallInteger('lead_days')->default(3)->after('allergens');
            $table->unsignedInteger('base_price')->nullable()->after('price');
            $table->unsignedInteger('per_tier_addon')->default(0)->after('base_price');
            $table->unsignedInteger('per_flavor_addon')->default(0)->after('per_tier_addon');
            $table->json('optional_addons')->nullable()->after('per_flavor_addon');
            $table->text('care_instructions')->nullable()->after('description');
        });
    }

    public function down(): void
    {
        Schema::table('cakes', function (Blueprint $table) {
            $table->dropColumn([
                'size_options',
                'ingredients',
                'allergens',
                'lead_days',
                'base_price',
                'per_tier_addon',
                'per_flavor_addon',
                'optional_addons',
                'care_instructions',
            ]);
        });
    }
};
