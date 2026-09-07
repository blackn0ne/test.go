<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, CalendarDays, ClipboardList } from '@lucide/vue';
import { computed } from 'vue';
import ExamAttemptController from '@/actions/App/Http/Controllers/ExamAttemptController';
import TablePagination from '@/components/TablePagination.vue';
import { Button } from '@/components/ui/button';
import ExamScreenLayout from '@/layouts/exam/ExamScreenLayout.vue';
import { formatExamScore } from '@/lib/examScores';
import { cn } from '@/lib/utils';
import type { ExamSection, PaginatedExamHistory } from '@/types/exam';

defineOptions({
    layout: null,
});

const props = defineProps<{
    sections: ExamSection[];
    attempts: PaginatedExamHistory;
}>();

function formatSubmittedAt(value: string | null): string {
    if (value === null) {
        return '—';
    }

    return new Intl.DateTimeFormat('kk-KZ', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    }).format(new Date(value));
}

function scorePercent(attempt: PaginatedExamHistory['data'][number]): number {
    if (attempt.max_score <= 0) {
        return 0;
    }

    return Math.round((attempt.total_score / attempt.max_score) * 100);
}

function scoreTone(percent: number): string {
    if (percent >= 80) {
        return 'from-emerald-500 to-teal-500';
    }

    if (percent >= 50) {
        return 'from-amber-500 to-orange-500';
    }

    return 'from-rose-500 to-pink-500';
}

const hasAttempts = computed(() => props.attempts.data.length > 0);
</script>

<template>
    <ExamScreenLayout
        :sections="props.sections"
        header-title="Менің сынақтарым"
    >
        <Head title="Менің сынақтарым" />

        <div
            class="flex min-h-[calc(100dvh-4rem)] flex-1 flex-col bg-gradient-to-b from-muted/20 via-background to-background"
        >
            <div class="flex w-full flex-1 flex-col gap-5 p-4 lg:p-6">
                <section
                    class="relative overflow-hidden rounded-2xl border border-border/60 bg-gradient-to-br from-background via-background to-muted/30 p-6 shadow-sm"
                >
                    <div
                        class="pointer-events-none absolute inset-0 bg-gradient-to-br from-sky-500/10 via-transparent to-violet-500/10"
                    />
                    <div class="relative flex items-start gap-4">
                        <div
                            class="flex size-12 shrink-0 items-center justify-center rounded-xl border border-border/60 bg-background shadow-sm"
                        >
                            <ClipboardList class="size-6 text-sky-600" />
                        </div>
                        <div class="space-y-1">
                            <h1 class="text-2xl font-bold tracking-tight">
                                Менің сынақтарым
                            </h1>
                            <p class="text-sm text-muted-foreground">
                                Аяқталған сынақтар тізімі және нәтижелер
                            </p>
                        </div>
                    </div>
                </section>

                <div
                    v-if="hasAttempts"
                    class="grid gap-3 xl:grid-cols-2"
                >
                    <article
                        v-for="attempt in props.attempts.data"
                        :key="attempt.id"
                        class="rounded-2xl border border-border/60 bg-background/80 p-5 shadow-sm transition-colors hover:border-border"
                    >
                        <div class="flex flex-wrap items-start justify-between gap-3">
                            <div class="min-w-0 space-y-2">
                                <h2 class="truncate text-base font-bold">
                                    {{ attempt.exam_title }}
                                </h2>
                                <p
                                    class="flex items-center gap-1.5 text-xs text-muted-foreground"
                                >
                                    <CalendarDays class="size-3.5 shrink-0" />
                                    {{ formatSubmittedAt(attempt.submitted_at) }}
                                </p>
                            </div>

                            <div class="text-right">
                                <p class="text-xs text-muted-foreground">Балл</p>
                                <p class="text-2xl font-black tabular-nums">
                                    {{ formatExamScore(attempt.total_score) }}
                                    <span
                                        class="text-sm font-semibold text-muted-foreground"
                                    >
                                        / {{ formatExamScore(attempt.max_score) }}
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="mt-4 h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                :class="
                                    cn(
                                        'h-full rounded-full bg-gradient-to-r transition-all duration-500',
                                        scoreTone(scorePercent(attempt)),
                                    )
                                "
                                :style="{
                                    width: `${scorePercent(attempt)}%`,
                                }"
                            />
                        </div>

                        <div class="mt-4 flex items-center justify-between gap-3">
                            <p class="text-xs font-medium text-muted-foreground">
                                {{ scorePercent(attempt) }}% нәтиже
                            </p>

                            <Button
                                v-if="attempt.can_view_result"
                                as-child
                                size="sm"
                                class="rounded-xl"
                            >
                                <Link
                                    :href="
                                        ExamAttemptController.result({
                                            exam: attempt.exam_id,
                                            attempt: attempt.id,
                                        }).url
                                    "
                                >
                                    Нәтижені көру
                                    <ArrowRight class="size-4" />
                                </Link>
                            </Button>
                        </div>
                    </article>
                </div>

                <div
                    v-else
                    class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border/70 bg-muted/10 px-6 py-16 text-center"
                >
                    <ClipboardList class="mb-3 size-10 text-muted-foreground/60" />
                    <p class="text-base font-semibold">Сынақтар әлі жоқ</p>
                    <p class="mt-1 max-w-sm text-sm text-muted-foreground">
                        Бірінші сынақты аяқтағаннан кейін осы жерде нәтиже
                        көрсетіледі.
                    </p>
                </div>

                <TablePagination
                    v-if="props.attempts.last_page > 1"
                    :current-page="props.attempts.current_page"
                    :last-page="props.attempts.last_page"
                    :per-page="props.attempts.per_page"
                    :total="props.attempts.total"
                    :links="props.attempts.links"
                />
            </div>
        </div>
    </ExamScreenLayout>
</template>
