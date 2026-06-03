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
        Schema::table('broadcast_contacts', function (Blueprint $table) {
            $table->string('type', 20)->default('person')->after('company_id');
            $table->string('company_name', 255)->nullable()->after('name');
            $table->string('document', 30)->nullable()->after('company_name');
            $table->string('address', 500)->nullable()->after('email');
            $table->string('city', 100)->nullable()->after('address');
            $table->string('state', 2)->nullable()->after('city');
            $table->text('notes')->nullable()->after('tags');
        });
    }

    public function down(): void
    {
        Schema::table('broadcast_contacts', function (Blueprint $table) {
            $table->dropColumn(['type', 'company_name', 'document', 'address', 'city', 'state', 'notes']);
        });
    }
};
