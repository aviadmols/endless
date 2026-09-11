<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memorials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('slug', 80)->unique();
            $table->string('first_name', 80);
            $table->string('last_name', 80)->nullable();
            $table->string('gender', 10)->default('male');
            $table->string('subtitle', 120)->nullable();       // "לזכרו של יקירנו"
            $table->date('birth_date')->nullable();
            $table->date('death_date')->nullable();
            $table->string('dates_text', 120)->nullable();     // free-text override (ACF "dates")
            $table->string('hebrew_dates', 120)->nullable();
            $table->string('religion', 20)->default('jewish'); // ACF "religion" (icon)
            $table->string('religion_icon_path')->nullable();  // custom uploaded icon
            $table->string('video_url', 500)->nullable();      // ACF "video" (URL)
            $table->string('portrait_image_path')->nullable();
            $table->string('portrait_video_path')->nullable();
            $table->string('hero_image_path')->nullable();
            $table->string('hero_video_path')->nullable();
            $table->string('biography_title', 160)->nullable(); // ACF "Biography-title"
            $table->longText('biography')->nullable();          // ACF "Biography" (WYSIWYG)
            $table->text('quote')->nullable();                  // ACF "quote"
            $table->string('quote_name', 120)->nullable();      // ACF "quote-name"
            $table->string('founder_name', 120)->nullable();    // "הוקם ע״י"
            $table->uuid('share_token')->unique();
            $table->string('visibility', 12)->default('private'); // private | unlisted
            $table->boolean('require_approval')->default(true);
            $table->boolean('notify_owner')->default(true);
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memorials');
    }
};
