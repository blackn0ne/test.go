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
        Schema::create('exam_attempt_question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_question_id')
                ->constrained('exam_attempt_questions')
                ->cascadeOnDelete();
            $table->foreignId('question_option_id')->constrained()->cascadeOnDelete();
            $table->string('select_group')->nullable();
            $table->string('label', 1);
            $table->longText('content');
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(
                ['exam_attempt_question_id', 'sort_order'],
                'exam_attempt_q_options_attempt_sort_index',
            );
            $table->index('question_option_id', 'exam_attempt_q_options_source_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempt_question_options');
    }
};
