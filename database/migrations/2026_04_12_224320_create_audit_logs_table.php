<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_name')->nullable();      // snapshot do nome (caso user seja deletado)
            $table->string('action', 20);                  // created, updated, deleted
            $table->string('auditable_type');               // App\Models\Contact, etc
            $table->unsignedBigInteger('auditable_id');
            $table->string('auditable_label')->nullable();  // resumo legível ("João Silva", "Pipeline Vendas")
            $table->json('old_values')->nullable();         // valores antes (update/delete)
            $table->json('new_values')->nullable();         // valores depois (create/update)
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('company_id');
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('user_id');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
