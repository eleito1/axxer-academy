<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->string('cover_image')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('published')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['product_id', 'slug']);
            $table->index(['product_id', 'published', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
