<?php

namespace App\Enums;

enum ExamGenerationMode: string
{
    case Manual = 'manual';
    case Generated = 'generated';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Ручной',
            self::Generated => 'Автогенерация',
        };
    }
}
