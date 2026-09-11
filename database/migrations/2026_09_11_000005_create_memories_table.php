<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memorial_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete(); // set when submitted by the owner
            $table->string('author_name', 120);
            $table->string('author_email', 160)->nullable();
            $table->string('author_phone', 32)->nullable();
            $table->string('title', 160)->nullable();
            $table->longText('body')->nullable();        // sanitized HTML
            $table->text('body_plain')->nullable();      // plain text for excerpts
            $table->string('status', 12)->default('pending'); // pending | approved | rejected
            $table->timestamp('approved_at')->nullable();
            $table->string('submitted_via', 12)->default('link'); // link | owner | admin
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['memorial_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memories');
    }
};
