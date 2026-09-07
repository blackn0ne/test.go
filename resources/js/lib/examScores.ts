export function formatExamScore(
    value: number | string | null | undefined,
): number {
    if (value === null || value === undefined || value === '') {
        return 0;
    }

    return Math.round(Number(value));
}
