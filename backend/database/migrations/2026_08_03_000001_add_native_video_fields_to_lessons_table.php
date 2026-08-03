<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('video_provider', 50)->nullable()->after('video_url');
            $table->string('video_path', 2048)->nullable()->after('video_provider');
            $table->string('video_original_name')->nullable()->after('video_path');
            $table->unsignedBigInteger('video_size')->nullable()->after('video_original_name');
            $table->string('video_extension', 10)->nullable()->after('video_size');
            $table->unsignedInteger('video_duration')->nullable()->after('video_extension');
            $table->timestamp('video_uploaded_at')->nullable()->after('video_duration');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn([
                'video_provider',
                'video_path',
                'video_original_name',
                'video_size',
                'video_extension',
                'video_duration',
                'video_uploaded_at',
            ]);
        });
    }
};
