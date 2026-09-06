import { computed, onMounted, onUnmounted, ref } from 'vue';

type ExamTimerOptions = {
    startedAt: string | null;
    durationMinutes: number | null;
    endsAt: string | null;
};

export function useExamTimer(options: ExamTimerOptions) {
    const now = ref(Date.now());
    let interval: ReturnType<typeof setInterval> | undefined;

    onMounted(() => {
        interval = setInterval(() => {
            now.value = Date.now();
        }, 1000);
    });

    onUnmounted(() => {
        if (interval !== undefined) {
            clearInterval(interval);
        }
    });

    const endTime = computed((): number | null => {
        const candidates: number[] = [];

        if (options.startedAt !== null && options.durationMinutes !== null) {
            candidates.push(
                new Date(options.startedAt).getTime() +
                    options.durationMinutes * 60 * 1000,
            );
        }

        if (options.endsAt !== null) {
            candidates.push(new Date(options.endsAt).getTime());
        }

        if (candidates.length === 0) {
            return null;
        }

        return Math.min(...candidates);
    });

    const remainingMs = computed((): number | null => {
        if (endTime.value === null) {
            return null;
        }

        return Math.max(0, endTime.value - now.value);
    });

    const formatted = computed((): string => {
        if (remainingMs.value === null) {
            return '--:--';
        }

        const totalSeconds = Math.floor(remainingMs.value / 1000);
        const hours = Math.floor(totalSeconds / 3600);
        const minutes = Math.floor((totalSeconds % 3600) / 60);
        const seconds = totalSeconds % 60;

        if (hours > 0) {
            return `${hours}:${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        }

        return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
    });

    const isExpired = computed(
        (): boolean => remainingMs.value === 0 && endTime.value !== null,
    );

    const isUrgent = computed((): boolean => {
        if (remainingMs.value === null) {
            return false;
        }

        return remainingMs.value <= 5 * 60 * 1000;
    });

    return {
        formatted,
        isExpired,
        isUrgent,
    };
}
