<script setup lang="ts">
import { CheckCircle2, CircleDashed, MinusCircle, XCircle } from 'lucide-vue';
import { computed } from 'vue';
import RichContent from '@/components/RichContent.vue';
import { cn } from '@/lib/utils';
import type { ExamResultQuestion } from '@/types/exam';

const props = defineProps<{
    question: ExamResultQuestion;
    index: number;
}>();

const status = computed(() => {
    if (props.question.score_awarded >= props.question.max_score) {
        return 'full';
    }

    if (props.question.score_awarded > 0) {
        return 'partial';
    }

    if (props.question.selected_option_ids.length === 0) {
        return 'unanswered';
    }

    return 'wrong';
});

const statusMeta = computed(() => {
    switch (status.value) {
        case 'full':
            return {
                label: 'Толық дұрыс',
                icon: CheckCircle2,
                card: 'border-emerald-500/30 bg-emerald-500/5',
                badge: 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',
            };
        case 'partial':
            return {
                label: 'Жартылай дұрыс',
                icon: MinusCircle,
                card: 'border-amber-500/30 bg-amber-500/5',
                badge: 'bg-amber-500/15 text-amber-700 dark:text-amber-300',
            };
        case 'unanswered':
            return {
                label: 'Жауапсыз',
                icon: CircleDashed,
                card: 'border-muted-foreground/20 bg-muted/20',
                badge: 'bg-muted text-muted-foreground',
            };
        default:
            return {
                label: 'Қате',
                icon: XCircle,
                card: 'border-rose-500/30 bg-rose-500/5',
                badge: 'bg-rose-500/15 text-rose-700 dark:text-rose-300',
            };
    }
});

function optionState(optionId: number): 'correct' | 'wrong' | 'missed' | 'neutral' {
    const isSelected = props.question.selected_option_ids.includes(optionId);
    const isCorrect = props.question.correct_option_ids.includes(optionId);

    if (isCorrect && isSelected) {
        return 'correct';
    }

    if (isCorrect && ! isSelected) {
        return 'missed';
    }

    if (! isCorrect && isSelected) {
        return 'wrong';
    }

    return 'neutral';
}

function optionClasses(optionId: number): string {
    const state = optionState(optionId);

    return cn(
        'flex items-center gap-3 rounded-xl border px-3 py-2.5 text-left transition-colors',
        state === 'correct'
            && 'border-emerald-500/40 bg-emerald-500/10',
        state === 'missed'
            && 'border-sky-500/40 bg-sky-500/10',
        state === 'wrong'
            && 'border-rose-500/40 bg-rose-500/10',
        state === 'neutral'
            && 'border-border/60 bg-background/70 opacity-70',
    );
}

function selectedForDoubleGroup(group: 'first' | 'second'): number | null {
    const groupOptionIds = props.question.options
        .filter((option) => option.select_group === group)
        .map((option) => option.id);

    return (
        props.question.selected_option_ids.find((optionId) =>
            groupOptionIds.includes(optionId),
        ) ?? null
    );
}

function correctForDoubleGroup(group: 'first' | 'second'): number | null {
    const groupOptionIds = props.question.options
        .filter((option) => option.select_group === group)
        .map((option) => option.id);

    return (
        props.question.correct_option_ids.find((optionId) =>
            groupOptionIds.includes(optionId),
        ) ?? null
    );
}

function doubleGroupState(group: 'first' | 'second'): 'correct' | 'wrong' | 'unanswered' {
    const selected = selectedForDoubleGroup(group);
    const correct = correctForDoubleGroup(group);

    if (selected === null) {
        return 'unanswered';
    }

    return selected === correct ? 'correct' : 'wrong';
}
</script>

<template>
    <article
        :class="
            cn(
                'overflow-hidden rounded-2xl border shadow-sm',
                statusMeta.card,
            )
        "
    >
        <header
            class="flex flex-wrap items-start justify-between gap-3 border-b border-border/50 px-5 py-4"
        >
            <div class="space-y-1">
                <p class="text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                    Сұрақ {{ index + 1 }}
                </p>
                <div
                    class="question-body text-base font-bold leading-relaxed [&_.rich-content]:font-bold"
                >
                    <RichContent :content="question.body" />
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span
                    :class="
                        cn(
                            'inline-flex items-center gap-1.5 rounded-xl px-2.5 py-1 text-xs font-semibold',
                            statusMeta.badge,
                        )
                    "
                >
                    <component :is="statusMeta.icon" class="size-3.5" />
                    {{ statusMeta.label }}
                </span>
                <span
                    class="rounded-xl border border-border/60 bg-background/80 px-2.5 py-1 text-xs font-bold tabular-nums"
                >
                    {{ question.score_awarded }} / {{ question.max_score }}
                </span>
            </div>
        </header>

        <div class="space-y-4 px-5 py-4">
            <template v-if="question.type === 'double'">
                <div
                    v-for="group in (['first', 'second'] as const)"
                    :key="group"
                    class="space-y-2 rounded-xl border border-border/60 bg-background/70 p-3"
                >
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-sm font-semibold">
                            {{
                                group === 'first'
                                    ? question.double_first_prompt
                                    : question.double_second_prompt
                            }}
                        </p>
                        <span
                            :class="
                                cn(
                                    'rounded-lg px-2 py-0.5 text-xs font-semibold',
                                    doubleGroupState(group) === 'correct'
                                        && 'bg-emerald-500/15 text-emerald-700 dark:text-emerald-300',
                                    doubleGroupState(group) === 'wrong'
                                        && 'bg-rose-500/15 text-rose-700 dark:text-rose-300',
                                    doubleGroupState(group) === 'unanswered'
                                        && 'bg-muted text-muted-foreground',
                                )
                            "
                        >
                            {{
                                doubleGroupState(group) === 'correct'
                                    ? 'Дұрыс'
                                    : doubleGroupState(group) === 'wrong'
                                        ? 'Қате'
                                        : 'Жауапсыз'
                            }}
                        </span>
                    </div>

                    <div class="grid gap-2 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <p class="text-xs font-medium text-muted-foreground">
                                Сіздің таңдауыңыз
                            </p>
                            <div
                                v-if="selectedForDoubleGroup(group)"
                                :class="optionClasses(selectedForDoubleGroup(group)!)"
                            >
                                <span
                                    class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-border bg-muted text-sm font-bold"
                                >
                                    {{
                                        question.options.find(
                                            (option) =>
                                                option.id
                                                === selectedForDoubleGroup(group),
                                        )?.label
                                    }}
                                </span>
                                <RichContent
                                    :content="
                                        question.options.find(
                                            (option) =>
                                                option.id
                                                === selectedForDoubleGroup(group),
                                        )?.content ?? ''
                                    "
                                    compact
                                />
                            </div>
                            <p
                                v-else
                                class="rounded-xl border border-dashed border-border/70 px-3 py-2 text-sm text-muted-foreground"
                            >
                                Таңдалмаған
                            </p>
                        </div>

                        <div class="space-y-1.5">
                            <p class="text-xs font-medium text-muted-foreground">
                                Дұрыс жауап
                            </p>
                            <div
                                v-if="correctForDoubleGroup(group)"
                                :class="optionClasses(correctForDoubleGroup(group)!)"
                            >
                                <span
                                    class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-border bg-muted text-sm font-bold"
                                >
                                    {{
                                        question.options.find(
                                            (option) =>
                                                option.id
                                                === correctForDoubleGroup(group),
                                        )?.label
                                    }}
                                </span>
                                <RichContent
                                    :content="
                                        question.options.find(
                                            (option) =>
                                                option.id
                                                === correctForDoubleGroup(group),
                                        )?.content ?? ''
                                    "
                                    compact
                                />
                            </div>
                        </div>
                    </div>
                </div>
            </template>

            <template v-else>
                <div class="grid gap-2">
                    <div
                        v-for="option in question.options"
                        :key="option.id"
                        :class="optionClasses(option.id)"
                    >
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-lg border border-border bg-muted text-sm font-bold"
                        >
                            {{ option.label }}
                        </span>
                        <RichContent
                            v-if="option.content"
                            :content="option.content"
                            compact
                        />
                        <span
                            v-if="optionState(option.id) === 'correct'"
                            class="ml-auto text-xs font-semibold text-emerald-700 dark:text-emerald-300"
                        >
                            Дұрыс
                        </span>
                        <span
                            v-else-if="optionState(option.id) === 'missed'"
                            class="ml-auto text-xs font-semibold text-sky-700 dark:text-sky-300"
                        >
                            Күтілген
                        </span>
                        <span
                            v-else-if="optionState(option.id) === 'wrong'"
                            class="ml-auto text-xs font-semibold text-rose-700 dark:text-rose-300"
                        >
                            Қате таңдау
                        </span>
                    </div>
                </div>
            </template>
        </div>
    </article>
</template>
