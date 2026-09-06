<?php

namespace App\Support;

use App\Models\Exam;
use Carbon\CarbonInterface;

final class ExamPeriodFormatter
{
    /**
     * @var array<int, string>
     */
    private const KAZAKH_MONTHS = [
        1 => 'Қаңтар',
        2 => 'Ақпан',
        3 => 'Наурыз',
        4 => 'Сәуір',
        5 => 'Мамыр',
        6 => 'Маусым',
        7 => 'Шілде',
        8 => 'Тамыз',
        9 => 'Қыркүйек',
        10 => 'Қазан',
        11 => 'Қараша',
        12 => 'Желтоқсан',
    ];

    public static function label(?CarbonInterface $startsAt, ?CarbonInterface $endsAt): string
    {
        if ($startsAt !== null && $endsAt !== null) {
            return $startsAt->translatedFormat('j F Y').' — '.$endsAt->translatedFormat('j F Y');
        }

        if ($startsAt !== null) {
            return mb_convert_case($startsAt->translatedFormat('F'), MB_CASE_TITLE).' '.$startsAt->year;
        }

        return self::currentMonthYearKazakh();
    }

    public static function forExam(Exam $exam): string
    {
        return self::label($exam->starts_at, $exam->ends_at);
    }

    public static function currentMonthYearKazakh(?CarbonInterface $date = null): string
    {
        $date ??= now();
        $month = self::KAZAKH_MONTHS[(int) $date->format('n')] ?? $date->format('F');

        return $month.' '.$date->year;
    }
}
