<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_backups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('filename');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('tables_count')->default(0);
            $table->unsignedInteger('records_count')->default(0);
            $table->string('status')->default('generating'); // generating, ready, failed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_backups');
    }
};
