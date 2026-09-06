<?php

namespace App\Support;

use App\Enums\SubjectKind;
use App\Models\ExamBlueprintSection;
use App\Models\Subject;

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
     * Находит уже существующий предмет по name/code и помечает как обязательный.
     */
    public static function resolve(string $code): Subject
    {
        if (! isset(self::CODES[$code])) {
            throw new \InvalidArgumentException("Unknown core subject code: {$code}");
        }

        $name = self::CODES[$code];

        $subject = Subject::query()
            ->where('code', $code)
            ->orWhere('name', $name)
            ->orderBy('id')
            ->first();

        if ($subject === null) {
            throw new \RuntimeException(
                "Обязательный предмет «{$name}» не найден. Создайте его в справочнике и отметьте галочкой «Обязательный (ЕНТ)».",
            );
        }

        $subject->update([
            'code' => $code,
            'name' => $name,
            'kind' => SubjectKind::Core,
            'is_system' => true,
        ]);

        self::removeDuplicates($subject, $name);

        return $subject->fresh();
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

        $subject->update([
            'kind' => SubjectKind::Core,
            'is_system' => true,
            'code' => $code ?? $subject->code,
        ]);

        if ($code !== null) {
            self::removeDuplicates($subject, self::CODES[$code]);
        }

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

    private static function removeDuplicates(Subject $keep, string $name): void
    {
        Subject::query()
            ->where('name', $name)
            ->where('id', '!=', $keep->id)
            ->whereDoesntHave('questions')
            ->whereDoesntHave('blueprintSections')
            ->whereDoesntHave('directions')
            ->delete();
    }
}
