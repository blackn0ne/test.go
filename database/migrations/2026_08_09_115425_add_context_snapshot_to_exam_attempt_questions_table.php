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
        Schema::table('exam_attempt_questions', function (Blueprint $table) {
            $table->unsignedBigInteger('question_context_id')->nullable()->after('question_id');
            $table->string('context_title')->nullable()->after('body');
            $table->longText('context_body')->nullable()->after('context_title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_attempt_questions', function (Blueprint $table) {
            $table->dropColumn(['question_context_id', 'context_title', 'context_body']);
        });
    }
};
