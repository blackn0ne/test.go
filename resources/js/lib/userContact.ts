export function normalizePhoneDigits(value: string): string {
    let digits = value.replace(/\D/g, '');

    if (digits.startsWith('8') && digits.length === 11) {
        digits = `7${digits.slice(1)}`;
    }

    if (digits.length === 10) {
        digits = `7${digits}`;
    }

    return digits;
}

export function emailFromPhone(value: string): string {
    const digits = normalizePhoneDigits(value);

    return digits ? `${digits}@gotest.kz` : '';
}

export function formatPhoneInput(value: string): string {
    const digits = normalizePhoneDigits(value).slice(0, 11);

    if (digits.length <= 1) {
        return digits;
    }

    if (digits.length <= 4) {
        return `+7 (${digits.slice(1)}`;
    }

    if (digits.length <= 7) {
        return `+7 (${digits.slice(1, 4)}) ${digits.slice(4)}`;
    }

    if (digits.length <= 9) {
        return `+7 (${digits.slice(1, 4)}) ${digits.slice(4, 7)}-${digits.slice(7)}`;
    }

    return `+7 (${digits.slice(1, 4)}) ${digits.slice(4, 7)}-${digits.slice(7, 9)}-${digits.slice(9, 11)}`;
}
