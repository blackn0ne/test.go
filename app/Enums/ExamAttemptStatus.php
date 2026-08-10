<?php

namespace App\Enums;

enum ExamAttemptStatus: string
{
    case InProgress = 'in_progress';
    case Submitted = 'submitted';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'В процессе',
            self::Submitted => 'Завершён',
            self::Expired => 'Истёк',
        };
    }
}
