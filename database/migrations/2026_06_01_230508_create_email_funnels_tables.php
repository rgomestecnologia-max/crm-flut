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
        Schema::create('email_funnels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->string('name');
            $table->string('status', 20)->default('draft'); // draft, active, paused
            $table->string('trigger_type', 30)->default('manual'); // manual, tag, landing_page, flutchat, crm_stage
            $table->string('trigger_value')->nullable(); // tag name, page_id, widget_id, stage_id
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });

        Schema::create('email_funnel_steps', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('funnel_id');
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('type', 20); // email, delay, condition
            $table->json('config'); // subject, html_content, seconds, field, true_step_id, false_step_id
            $table->timestamps();
            $table->foreign('funnel_id')->references('id')->on('email_funnels')->cascadeOnDelete();
        });

        Schema::create('email_funnel_subscribers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('company_id')->index();
            $table->unsignedBigInteger('funnel_id');
            $table->unsignedBigInteger('contact_id'); // BroadcastContact
            $table->unsignedBigInteger('current_step_id')->nullable();
            $table->string('status', 20)->default('active'); // active, completed, unsubscribed, paused
            $table->timestamp('step_entered_at')->nullable(); // quando entrou no step atual
            $table->timestamp('entered_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->foreign('funnel_id')->references('id')->on('email_funnels')->cascadeOnDelete();
            $table->unique(['funnel_id', 'contact_id']);
        });

        Schema::create('email_funnel_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscriber_id');
            $table->unsignedBigInteger('step_id');
            $table->string('action', 20); // sent, opened, clicked, bounced, failed
            $table->string('message_id')->nullable(); // SendGrid message ID
            $table->timestamps();
            $table->foreign('subscriber_id')->references('id')->on('email_funnel_subscribers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_funnel_logs');
        Schema::dropIfExists('email_funnel_subscribers');
        Schema::dropIfExists('email_funnel_steps');
        Schema::dropIfExists('email_funnels');
    }
};
