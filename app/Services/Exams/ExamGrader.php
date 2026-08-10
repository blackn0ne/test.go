<?php

namespace App\Services\Exams;

use App\Models\Question;
use App\Services\QuestionScorer;

class ExamGrader
{
    public function __construct(
        private readonly QuestionScorer $scorer,
    ) {}

    /**
     * @param  array<int>  $sourceOptionIds  IDs from question_options table
     */
    public function gradeQuestion(Question $question, array $sourceOptionIds): float
    {
        return $this->scorer->score($question, $sourceOptionIds);
    }
}
