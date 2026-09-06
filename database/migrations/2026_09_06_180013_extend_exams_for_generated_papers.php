<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->string('generation_mode')->default('manual')->after('subject_id');
            $table->foreignId('exam_blueprint_id')->nullable()->after('generation_mode')
                ->constrained()->nullOnDelete();
            $table->foreignId('direction_id')->nullable()->after('exam_blueprint_id')
                ->constrained()->nullOnDelete();

            $table->foreignId('subject_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('exams', function (Blueprint $table) {
            $table->dropConstrainedForeignId('direction_id');
            $table->dropConstrainedForeignId('exam_blueprint_id');
            $table->dropColumn('generation_mode');
        });
    }
};
