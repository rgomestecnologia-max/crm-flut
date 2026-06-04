<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_in_bio_pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->enum('status', ['draft', 'published'])->default('draft');
            $table->text('bio_text')->nullable();
            $table->text('avatar_url')->nullable();
            $table->json('theme')->nullable();
            $table->text('custom_css')->nullable();
            $table->string('custom_domain')->nullable();
            $table->string('fb_pixel', 50)->nullable();
            $table->string('ga_id', 50)->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('link_in_bio_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('page_id')->constrained('link_in_bio_pages')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('type', 20)->default('link'); // link, header, divider, social
            $table->string('title')->nullable();
            $table->text('url')->nullable();
            $table->string('icon', 50)->nullable();
            $table->text('thumbnail_url')->nullable();
            $table->json('config')->nullable();
            $table->unsignedInteger('clicks_count')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_in_bio_links');
        Schema::dropIfExists('link_in_bio_pages');
    }
};
