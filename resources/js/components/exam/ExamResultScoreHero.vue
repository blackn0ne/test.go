<script setup lang="ts">
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import { formatExamScore } from '@/lib/examScores';
import type { ExamResultAttempt } from '@/types/exam';

const props = defineProps<{
    attempt: ExamResultAttempt;
}>();

const totalScore = computed(() => formatExamScore(props.attempt.total_score));
const maxScore = computed(() => formatExamScore(props.attempt.max_score));

const percentage = computed(() => {
    if (maxScore.value <= 0) {
        return 0;
    }

    return Math.round((totalScore.value / maxScore.value) * 100);
});

const scoreTone = computed(() => {
    if (percentage.value >= 80) {
        return {
            ring: 'stroke-emerald-500',
            glow: 'from-emerald-500/20 via-emerald-400/5 to-transparent',
            badge: 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-300',
        };
    }

    if (percentage.value >= 50) {
        return {
            ring: 'stroke-amber-500',
            glow: 'from-amber-500/20 via-amber-400/5 to-transparent',
            badge: 'bg-amber-500/10 text-amber-700 dark:text-amber-300',
        };
    }

    return {
        ring: 'stroke-rose-500',
        glow: 'from-rose-500/20 via-rose-400/5 to-transparent',
        badge: 'bg-rose-500/10 text-rose-700 dark:text-rose-300',
    };
});

const circumference = 2 * Math.PI * 54;
const dashOffset = computed(
    () => circumference - (percentage.value / 100) * circumference,
);
</script>

<template>
    <section
        class="relative overflow-hidden rounded-2xl border border-border/60 bg-gradient-to-br from-background via-background to-muted/30 p-6 shadow-sm"
    >
        <div
            :class="
                cn(
                    'pointer-events-none absolute inset-0 bg-gradient-to-br opacity-80',
                    scoreTone.glow,
                )
            "
        />

        <div
            class="relative flex flex-col gap-6 md:flex-row md:items-center md:justify-between"
        >
            <div class="space-y-2">
                <p
                    class="inline-flex items-center rounded-xl px-3 py-1 text-xs font-semibold uppercase tracking-wide"
                    :class="scoreTone.badge"
                >
                    Экзамен аяқталды
                </p>
                <p class="max-w-xl text-sm text-muted-foreground">
                    Әр бөлім бойынша нәтиже, сіздің жауаптарыңыз және дұрыс
                    жауаптар төменде көрсетілген.
                </p>
            </div>

            <div class="flex items-center gap-5 self-start md:self-center">
                <div class="relative size-32 shrink-0">
                    <svg
                        class="size-full -rotate-90"
                        viewBox="0 0 120 120"
                        aria-hidden="true"
                    >
                        <circle
                            cx="60"
                            cy="60"
                            r="54"
                            fill="none"
                            class="stroke-muted/60"
                            stroke-width="10"
                        />
                        <circle
                            cx="60"
                            cy="60"
                            r="54"
                            fill="none"
                            :class="scoreTone.ring"
                            stroke-width="10"
                            stroke-linecap="round"
                            :stroke-dasharray="circumference"
                            :stroke-dashoffset="dashOffset"
                            class="transition-all duration-700 ease-out"
                        />
                    </svg>
                    <div
                        class="absolute inset-0 flex flex-col items-center justify-center"
                    >
                        <span class="text-3xl font-black tabular-nums">
                            {{ percentage }}%
                        </span>
                        <span class="text-xs text-muted-foreground">нәтиже</span>
                    </div>
                </div>

                <div class="space-y-1">
                    <p class="text-sm text-muted-foreground">Жалпы балл</p>
                    <p class="text-4xl font-black tabular-nums leading-none">
                        {{ totalScore }}
                        <span class="text-xl font-semibold text-muted-foreground">
                            / {{ maxScore }}
                        </span>
                    </p>
                </div>
            </div>
        </div>
    </section>
</template>
