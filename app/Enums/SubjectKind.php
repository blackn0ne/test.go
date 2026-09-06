<?php

namespace App\Enums;

enum SubjectKind: string
{
    case Core = 'core';
    case Profile = 'profile';

    public function label(): string
    {
        return match ($this) {
            self::Core => 'Обязательный',
            self::Profile => 'Профильный',
        };
    }
}
