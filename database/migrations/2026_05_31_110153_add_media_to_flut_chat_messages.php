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
        Schema::table('flut_chat_messages', function (Blueprint $table) {
            $table->string('media_url')->nullable()->after('content');
            $table->string('media_type', 20)->nullable()->after('media_url'); // image, video, audio, document
            $table->string('media_filename')->nullable()->after('media_type');
        });
    }

    public function down(): void
    {
        Schema::table('flut_chat_messages', function (Blueprint $table) {
            $table->dropColumn(['media_url', 'media_type', 'media_filename']);
        });
    }
};
