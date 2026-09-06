<?php

namespace App\Support;

use App\Models\ExamAttemptQuestion;
use App\Models\User;
use Illuminate\Support\Collection;

final class ExamSectionCatalog
{
    /**
     * @return list<array{order: int, name: string, subject_id: int|null, kind: string}>
     */
    public static function forUser(User $user): array
    {
        $user->loadMissing('direction.subjects');

        $sections = [];
        $order = 1;

        foreach (CoreSubjects::CODES as $name) {
            $sections[] = [
                'order' => $order,
                'name' => $name,
                'subject_id' => null,
                'kind' => 'core',
            ];
            $order++;
        }

        foreach ($user->direction?->subjects ?? [] as $subject) {
            $sections[] = [
                'order' => $order,
                'name' => $subject->name,
                'subject_id' => $subject->id,
                'kind' => 'profile',
            ];
            $order++;
        }

        return $sections;
    }

    /**
     * @param  Collection<int, ExamAttemptQuestion>  $questions
     * @return list<array{order: int, name: string, subject_id: int|null, kind: string, question_count: int}>
     */
    public static function fromAttemptQuestions(Collection $questions): array
    {
        return $questions
            ->groupBy('section_order')
            ->sortKeys()
            ->map(function (Collection $group, int|string $sectionOrder): array {
                /** @var ExamAttemptQuestion $first */
                $first = $group->first();

                return [
                    'order' => (int) $sectionOrder,
                    'name' => $first->subject_name ?? 'Бөлім '.((int) $sectionOrder),
                    'subject_id' => $first->subject_id,
                    'kind' => $first->section_order <= count(CoreSubjects::CODES) ? 'core' : 'profile',
                    'question_count' => $group->count(),
                ];
            })
            ->values()
            ->all();
    }
}
