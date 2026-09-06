<?php

namespace App\Support;

use App\Models\Exam;
use Illuminate\Support\Carbon;

final class ExamPeriodFormatter
{
    public static function label(?Carbon $startsAt, ?Carbon $endsAt): string
    {
        if ($startsAt !== null && $endsAt !== null) {
            return $startsAt->translatedFormat('j F Y').' — '.$endsAt->translatedFormat('j F Y');
        }

        if ($startsAt !== null) {
            return mb_convert_case($startsAt->translatedFormat('F'), MB_CASE_TITLE).' '.$startsAt->year;
        }

        return mb_convert_case(now()->translatedFormat('F'), MB_CASE_TITLE).' '.now()->year;
    }

    public static function forExam(Exam $exam): string
    {
        return self::label($exam->starts_at, $exam->ends_at);
    }
}
