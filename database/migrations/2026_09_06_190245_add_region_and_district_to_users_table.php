<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('region_id')
                ->nullable()
                ->after('direction_id')
                ->constrained()
                ->nullOnDelete();
            $table->foreignId('district_id')
                ->nullable()
                ->after('region_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('district_id');
            $table->dropConstrainedForeignId('region_id');
        });
    }
};
