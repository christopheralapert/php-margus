<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
            $table->string('locale', 2)->default('et');
            $table->boolean('is_admin')->default(false);
            $table->timestamp('last_active_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['locale', 'is_admin', 'last_active_at']);
            $table->string('name')->nullable(false)->change();
        });
    }
};
