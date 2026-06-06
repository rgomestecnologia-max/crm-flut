<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('internal_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('avatar_url')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('internal_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('internal_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at')->useCurrent();
            $table->unique(['group_id', 'user_id']);
        });

        Schema::table('internal_messages', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('recipient_id')
                ->constrained('internal_groups')->cascadeOnDelete();
        });

        // Tornar recipient_id nullable (para mensagens de grupo que não têm recipient)
        DB::statement('ALTER TABLE internal_messages MODIFY recipient_id BIGINT UNSIGNED NULL');
    }

    public function down(): void
    {
        Schema::table('internal_messages', function (Blueprint $table) {
            $table->dropForeign(['group_id']);
            $table->dropColumn('group_id');
        });
        Schema::dropIfExists('internal_group_members');
        Schema::dropIfExists('internal_groups');
    }
};
