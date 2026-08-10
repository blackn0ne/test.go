<?php

use App\Models\Question;
use App\Services\Questions\QuestionAnswerKeyBuilder;
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
            $table->json('answer_key')->nullable()->after('body');
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->index(
                ['question_id', 'is_correct'],
                'question_options_question_correct_index',
            );
        });

        $builder = app(QuestionAnswerKeyBuilder::class);

        Question::query()->with('options')->chunkById(200, function ($questions) use ($builder): void {
            foreach ($questions as $question) {
                $question->update([
                    'answer_key' => $builder->build($question),
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn('answer_key');
        });

        Schema::table('question_options', function (Blueprint $table) {
            $table->dropIndex('question_options_question_correct_index');
        });
    }
};
