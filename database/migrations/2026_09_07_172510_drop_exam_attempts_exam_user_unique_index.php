<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropUnique('exam_attempts_exam_user_unique');
            $table->index(['exam_id', 'user_id', 'status'], 'exam_attempts_exam_user_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table) {
            $table->dropIndex('exam_attempts_exam_user_status_index');
            $table->unique(['exam_id', 'user_id'], 'exam_attempts_exam_user_unique');
        });
    }
};
