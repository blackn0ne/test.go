<?php

namespace App\Support;

class HtmlContent
{
    public static function hasMeaningfulContent(string $html): bool
    {
        if (trim(strip_tags($html)) !== '') {
            return true;
        }

        return preg_match('/<img[\s>]/i', $html) === 1;
    }
}
