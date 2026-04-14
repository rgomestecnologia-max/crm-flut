<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('department_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unique(['department_id', 'user_id']);
            $table->index('user_id');
        });

        // Popular pivô com o departamento principal já existente em users.department_id.
        // Garante que toda regra que passar a usar a pivô já encontre os vínculos atuais.
        DB::table('users')
            ->whereNotNull('department_id')
            ->orderBy('id')
            ->each(function ($user) {
                DB::table('department_user')->insertOrIgnore([
                    'department_id' => $user->department_id,
                    'user_id'       => $user->id,
                ]);
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('department_user');
    }
};
