<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('name');
            $table->string('kind')->default('profile')->after('code');
            $table->boolean('is_system')->default(false)->after('kind');
        });
    }

    public function down(): void
    {
        Schema::table('subjects', function (Blueprint $table) {
            $table->dropColumn(['code', 'kind', 'is_system']);
        });
    }
};
