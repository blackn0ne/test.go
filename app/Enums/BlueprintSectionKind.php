<?php

namespace App\Enums;

enum BlueprintSectionKind: string
{
    case Core = 'core';
    case Profile = 'profile';

    public function label(): string
    {
        return match ($this) {
            self::Core => 'Обязательный предмет',
            self::Profile => 'Профильный предмет',
        };
    }
}
