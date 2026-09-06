<?php

namespace App\Support;

use App\Enums\SubjectKind;
use App\Models\Exam;
use App\Models\ExamBlueprintSection;
use App\Models\Group;
use App\Models\Question;
use App\Models\QuestionContext;
use App\Models\Subject;
use Illuminate\Support\Collection;

final class CoreSubjects
{
    /**
     * @var array<string, string>
     */
    public const CODES = [
        'reading_literacy' => 'Оқу сауаттылығы',
        'math_literacy' => 'Математикалық сауаттылық',
        'kazakhstan_history' => 'Қазақстан тарихы',
    ];

    public static function codeForName(string $name): ?string
    {
        foreach (self::CODES as $code => $canonicalName) {
            if ($canonicalName === $name) {
                return $code;
            }
        }

        return null;
    }

    public static function isCoreName(string $name): bool
    {
        return self::codeForName($name) !== null;
    }

    /**
     * Находит уже существующий предмет по name/code, сливает дубликаты и помечает как обязательный.
     */
    public static function resolve(string $code): Subject
    {
        if (! isset(self::CODES[$code])) {
            throw new \InvalidArgumentException("Unknown core subject code: {$code}");
        }

        $name = self::CODES[$code];

        /** @var Collection<int, Subject> $matches */
        $matches = Subject::query()
            ->withCount('questions')
            ->where('code', $code)
            ->orWhere('name', $name)
            ->get();

        if ($matches->isEmpty()) {
            throw new \RuntimeException(
                "Обязательный предмет «{$name}» не найден. Создайте его в справочнике и отметьте галочкой «Обязательный (ЕНТ)».",
            );
        }

        $keeper = self::chooseKeeper($matches);

        foreach ($matches->where('id', '!=', $keeper->id) as $duplicate) {
            self::mergeDuplicateInto($keeper, $duplicate);
        }

        $keeper->update([
            'code' => $code,
            'name' => $name,
            'kind' => SubjectKind::Core,
            'is_system' => true,
        ]);

        return $keeper->fresh();
    }

    /**
     * @return list<Subject>
     */
    public static function resolveAll(): array
    {
        return array_map(
            fn (string $code) => self::resolve($code),
            array_keys(self::CODES),
        );
    }

    public static function markAsCore(Subject $subject): Subject
    {
        $code = self::codeForName($subject->name);

        if ($code !== null) {
            return self::resolve($code);
        }

        $subject->update([
            'kind' => SubjectKind::Core,
            'is_system' => true,
            'code' => $subject->code,
        ]);

        return $subject->fresh();
    }

    public static function markAsProfile(Subject $subject): Subject
    {
        if (ExamBlueprintSection::query()->where('subject_id', $subject->id)->exists()) {
            throw new \RuntimeException('Предмет используется в шаблоне ЕНТ и не может стать профильным.');
        }

        $subject->update([
            'kind' => SubjectKind::Profile,
            'is_system' => false,
            'code' => null,
        ]);

        return $subject->fresh();
    }

    /**
     * Основной — тот, где уже лежат вопросы (ваш оригинальный предмет).
     * Пустой дубликат от сидера удаляется.
     *
     * @param  Collection<int, Subject>  $matches
     */
    private static function chooseKeeper(Collection $matches): Subject
    {
        return $matches
            ->sortBy('id')
            ->sortByDesc(fn (Subject $subject) => $subject->questions_count ?? $subject->questions()->count())
            ->first();
    }

    private static function mergeDuplicateInto(Subject $keeper, Subject $duplicate): void
    {
        Question::query()
            ->where('subject_id', $duplicate->id)
            ->update(['subject_id' => $keeper->id]);

        QuestionContext::query()
            ->where('subject_id', $duplicate->id)
            ->update(['subject_id' => $keeper->id]);

        Group::query()
            ->where('subject_id', $duplicate->id)
            ->update(['subject_id' => $keeper->id]);

        Exam::query()
            ->where('subject_id', $duplicate->id)
            ->update(['subject_id' => $keeper->id]);

        ExamBlueprintSection::query()
            ->where('subject_id', $duplicate->id)
            ->update(['subject_id' => $keeper->id]);

        $classIds = $duplicate->schoolClasses()->pluck('school_classes.id');

        if ($classIds->isNotEmpty()) {
            $keeper->schoolClasses()->syncWithoutDetaching($classIds->all());
        }

        $duplicate->schoolClasses()->detach();
        $duplicate->delete();
    }
}
