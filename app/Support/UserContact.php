<?php

namespace App\Support;

class UserContact
{
    public static function normalizePhone(string $phone): string
    {
        $digits = preg_replace('/\D/', '', $phone) ?? '';

        if (str_starts_with($digits, '8') && strlen($digits) === 11) {
            $digits = '7'.substr($digits, 1);
        }

        if (strlen($digits) === 10) {
            $digits = '7'.$digits;
        }

        return $digits;
    }

    public static function emailFromPhone(string $phone): string
    {
        return self::normalizePhone($phone).'@gotest.kz';
    }

    public static function formatPhoneForDisplay(string $phone): string
    {
        $digits = self::normalizePhone($phone);

        if (! preg_match('/^7\d{10}$/', $digits)) {
            return $phone;
        }

        return sprintf(
            '+7 (%s) %s-%s-%s',
            substr($digits, 1, 3),
            substr($digits, 4, 3),
            substr($digits, 7, 2),
            substr($digits, 9, 2),
        );
    }
}
