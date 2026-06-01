<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('title');
            $table->string('slug')->index();
            $table->text('description')->nullable();
            $table->string('favicon')->nullable();
            $table->string('og_image')->nullable();
            $table->string('status', 20)->default('draft'); // draft, published
            $table->string('custom_domain')->nullable();
            $table->string('fb_pixel')->nullable();
            $table->string('ga_id')->nullable();
            $table->text('custom_css')->nullable();
            $table->string('notification_email')->nullable();
            $table->string('thank_you_url')->nullable();
            $table->unsignedBigInteger('flutchat_widget_id')->nullable();
            $table->unsignedInteger('views_count')->default(0);
            $table->timestamps();

            $table->unique(['company_id', 'slug']);
        });

        Schema::create('landing_page_sections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('landing_page_id');
            $table->string('type', 30); // hero, features, testimonials, form, gallery, video, stats, faq, map, cta, whatsapp, flutchat, text, header, footer
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('config'); // textos, cores, imagens, campos, etc.
            $table->boolean('visible')->default(true);
            $table->timestamps();

            $table->foreign('landing_page_id')->references('id')->on('landing_pages')->cascadeOnDelete();
        });

        Schema::create('landing_page_leads', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('landing_page_id');
            $table->json('data');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent', 255)->nullable();
            $table->string('page_url')->nullable();
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->timestamps();

            $table->foreign('landing_page_id')->references('id')->on('landing_pages')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_page_leads');
        Schema::dropIfExists('landing_page_sections');
        Schema::dropIfExists('landing_pages');
    }
};
