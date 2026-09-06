<?php

namespace App\Services\PromoCodes;

use App\Models\PromoCode;

class PromoCodeGenerator
{
    private const string CHARSET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * @return list<string>
     */
    public function generateUniqueCodes(int $count): array
    {
        if ($count < 1) {
            return [];
        }

        $codes = [];

        while (count($codes) < $count) {
            $code = $this->generateCode();

            if (isset($codes[$code])) {
                continue;
            }

            if (PromoCode::query()->where('code', $code)->exists()) {
                continue;
            }

            $codes[$code] = true;
        }

        return array_keys($codes);
    }

    private function generateCode(): string
    {
        $code = '';

        for ($index = 0; $index < 5; $index++) {
            $code .= self::CHARSET[random_int(0, strlen(self::CHARSET) - 1)];
        }

        return $code;
    }
}
