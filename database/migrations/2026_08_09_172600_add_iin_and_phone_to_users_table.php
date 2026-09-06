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
        Schema::table('users', function (Blueprint $table) {
            $table->string('iin', 12)->nullable()->unique()->after('name');
            $table->string('phone', 11)->nullable()->unique()->after('iin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['iin']);
            $table->dropUnique(['phone']);
            $table->dropColumn(['iin', 'phone']);
        });
    }
};
