<?php

namespace App\Services\Exams;

use App\Models\Question;

readonly class GeneratedPaperItem
{
    public function __construct(
        public Question $question,
        public int $sortOrder,
        public int $sectionOrder,
        public int $subjectId,
        public string $subjectName,
    ) {}
}
