<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('video_url', 2048);
            $table->unsignedInteger('duration')->nullable()->comment('Duração em segundos');
            $table->string('support_material', 2048)->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('published')->default(false);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['module_id', 'published', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
