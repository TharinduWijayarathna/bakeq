<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->string('platform'); // tiktok|instagram|facebook
            $table->string('title');
            $table->string('url');
            $table->text('embed_html')->nullable();
            $table->string('image_path')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
