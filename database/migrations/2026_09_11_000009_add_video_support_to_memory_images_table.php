<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A memory can now carry videos as well as photos, so the media table gains a type
 * (and a poster for videos that have a still frame).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('memory_images', function (Blueprint $table) {
            $table->string('type', 10)->default('image')->after('memory_id');
            $table->string('poster_path')->nullable()->after('thumb_path');
            $table->string('mime', 60)->nullable()->after('poster_path');
        });
    }

    public function down(): void
    {
        Schema::table('memory_images', function (Blueprint $table) {
            $table->dropColumn(['type', 'poster_path', 'mime']);
        });
    }
};
