<?php

namespace Tests\Unit;

use App\Support\UserContact;

test('user contact builds email from normalized phone', function () {
    expect(UserContact::normalizePhone('+7 (777) 123-45-67'))->toBe('77771234567')
        ->and(UserContact::emailFromPhone('87771234567'))->toBe('77771234567@gotest.kz');
});
