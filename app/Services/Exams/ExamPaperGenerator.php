<?php

namespace App\Services\Exams;

use App\Enums\QuestionType;
use App\Models\Direction;
use App\Models\Exam;
use App\Models\ExamBlueprintSection;
use App\Models\ExamBlueprintSlotRule;
use App\Models\Question;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ExamPaperGenerator
{
    public function __construct(
        private readonly StudentQuestionHistory $history,
    ) {}

    /**
     * @return list<GeneratedPaperItem>
     */
    public function generate(Exam $exam, User $user): array
    {
        $exam->loadMissing([
            'blueprint.sections.slotRules',
            'blueprint.sections.subject',
            'direction.subjects',
        ]);

        if ($exam->blueprint === null) {
            throw ValidationException::withMessages([
                'exam' => 'У экзамена не задан шаблон генерации.',
            ]);
        }

        if ($exam->direction === null) {
            throw ValidationException::withMessages([
                'exam' => 'У экзамена не выбрано направление.',
            ]);
        }

        $paper = [];
        $selectedIds = [];
        $globalSort = 0;

        foreach ($exam->blueprint->sections as $section) {
            $subject = $this->resolveSubject($section, $exam->direction);

            foreach ($section->slotRules as $rule) {
                $questions = $this->pickForRule(
                    $subject,
                    $rule,
                    $user,
                    $selectedIds,
                );

                foreach ($questions as $question) {
                    $paper[] = new GeneratedPaperItem(
                        question: $question,
                        sortOrder: $globalSort,
                        sectionOrder: $section->sort_order,
                        subjectId: $subject->id,
                        subjectName: $subject->name,
                    );

                    $selectedIds[] = $question->id;
                    $globalSort++;
                }
            }
        }

        return $paper;
    }

    private function resolveSubject(ExamBlueprintSection $section, Direction $direction): Subject
    {
        if ($section->isProfile()) {
            $subject = $direction->subjectAtPosition((int) $section->profile_position);

            if ($subject === null) {
                throw ValidationException::withMessages([
                    'direction' => 'Направление не содержит профильный предмет на позиции '.$section->profile_position.'.',
                ]);
            }

            return $subject;
        }

        if ($section->subject === null) {
            throw ValidationException::withMessages([
                'blueprint' => 'Секция шаблона не привязана к предмету.',
            ]);
        }

        return $section->subject;
    }

    /**
     * @param  list<int>  $selectedIds
     * @return Collection<int, Question>
     */
    private function pickForRule(
        Subject $subject,
        ExamBlueprintSlotRule $rule,
        User $user,
        array $selectedIds,
    ): Collection {
        $count = $rule->slotCount();
        $exclude = $this->history->mergeExcluded($user, $selectedIds);

        if ($rule->requires_context) {
            return $this->pickContextBlock($subject, $rule->question_type, $count, $exclude, $selectedIds);
        }

        return $this->pickRandomQuestions($subject, $rule->question_type, $count, $exclude, $selectedIds);
    }

    /**
     * @param  list<int>  $exclude
     * @param  list<int>  $selectedIds
     * @return Collection<int, Question>
     */
    private function pickContextBlock(
        Subject $subject,
        QuestionType $type,
        int $count,
        array $exclude,
        array $selectedIds,
    ): Collection {
        $contextIds = Question::query()
            ->where('subject_id', $subject->id)
            ->where('type', $type)
            ->whereNotNull('question_context_id')
            ->whereNotIn('id', $exclude)
            ->select('question_context_id')
            ->groupBy('question_context_id')
            ->havingRaw('COUNT(*) >= ?', [$count])
            ->inRandomOrder()
            ->pluck('question_context_id');

        if ($contextIds->isEmpty()) {
            $contextIds = Question::query()
                ->where('subject_id', $subject->id)
                ->where('type', $type)
                ->whereNotNull('question_context_id')
                ->whereNotIn('id', $selectedIds)
                ->select('question_context_id')
                ->groupBy('question_context_id')
                ->havingRaw('COUNT(*) >= ?', [$count])
                ->inRandomOrder()
                ->pluck('question_context_id');
        }

        if ($contextIds->isEmpty()) {
            throw ValidationException::withMessages([
                'questions' => "Недостаточно контекстных вопросов по предмету «{$subject->name}» ({$type->label()}).",
            ]);
        }

        $contextId = (int) $contextIds->first();

        $questions = Question::query()
            ->where('subject_id', $subject->id)
            ->where('type', $type)
            ->where('question_context_id', $contextId)
            ->whereNotIn('id', $exclude)
            ->inRandomOrder()
            ->limit($count)
            ->get();

        if ($questions->count() < $count) {
            $questions = Question::query()
                ->where('subject_id', $subject->id)
                ->where('type', $type)
                ->where('question_context_id', $contextId)
                ->whereNotIn('id', $selectedIds)
                ->inRandomOrder()
                ->limit($count)
                ->get();
        }

        if ($questions->count() < $count) {
            throw ValidationException::withMessages([
                'questions' => "Недостаточно контекстных вопросов по предмету «{$subject->name}».",
            ]);
        }

        return $questions->sortBy('id')->values();
    }

    /**
     * @param  list<int>  $exclude
     * @param  list<int>  $selectedIds
     * @return Collection<int, Question>
     */
    private function pickRandomQuestions(
        Subject $subject,
        QuestionType $type,
        int $count,
        array $exclude,
        array $selectedIds,
    ): Collection {
        $questions = Question::query()
            ->where('subject_id', $subject->id)
            ->where('type', $type)
            ->whereNotIn('id', $exclude)
            ->inRandomOrder()
            ->limit($count)
            ->get();

        if ($questions->count() < $count) {
            $questions = Question::query()
                ->where('subject_id', $subject->id)
                ->where('type', $type)
                ->whereNotIn('id', $selectedIds)
                ->inRandomOrder()
                ->limit($count)
                ->get();
        }

        if ($questions->count() < $count) {
            throw ValidationException::withMessages([
                'questions' => "Недостаточно вопросов «{$type->label()}» по предмету «{$subject->name}».",
            ]);
        }

        return $questions;
    }
}
