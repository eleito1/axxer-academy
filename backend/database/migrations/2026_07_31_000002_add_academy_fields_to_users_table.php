<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('company')->after('name');
            $table->string('city')->after('company');
            $table->string('whatsapp', 30)->after('city');
            $table->string('status', 20)->default('pendente')->index()->after('password');
            $table->string('role', 20)->default('aluno')->index()->after('status');
            $table->foreignId('interested_product_id')->nullable()->after('role')->constrained('products')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('interested_product_id');
            $table->dropColumn(['company', 'city', 'whatsapp', 'status', 'role']);
        });
    }
};
