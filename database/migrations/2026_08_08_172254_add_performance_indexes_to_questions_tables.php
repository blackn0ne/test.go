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
        Schema::table('questions', function (Blueprint $table) {
            $table->index(['subject_id', 'type'], 'questions_subject_type_index');
            $table->index(['subject_id', 'created_at'], 'questions_subject_created_index');
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->index(['question_id', 'sort_order'], 'question_options_question_sort_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropIndex('questions_subject_type_index');
            $table->dropIndex('questions_subject_created_index');
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->dropIndex('question_options_question_sort_index');
        });
    }
};
