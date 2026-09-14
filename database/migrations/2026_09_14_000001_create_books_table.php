<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memorial_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('content', 20)->default('both');       // App\Enums\BookContent
            $table->string('size', 20)->default('portrait_21_28'); // App\Enums\BookSize
            $table->unsignedSmallInteger('copies')->default(1);
            $table->unsignedSmallInteger('page_count')->default(0); // last composed length, for the summary
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
