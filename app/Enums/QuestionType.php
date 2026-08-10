<?php

namespace App\Enums;

enum QuestionType: string
{
    case Single = 'single';
    case Multiple = 'multiple';
    case Double = 'double';

    public function label(): string
    {
        return match ($this) {
            self::Single => 'Один ответ',
            self::Multiple => 'Несколько ответов',
            self::Double => 'Два селекта',
        };
    }

    public function maxScore(): int
    {
        return match ($this) {
            self::Single => 1,
            self::Multiple, self::Double => 2,
        };
    }
}
