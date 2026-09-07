<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    FileText,
    HelpCircle,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ExamAttemptController from '@/actions/App/Http/Controllers/ExamAttemptController';
import ExamAnswerOptions from '@/components/exam/ExamAnswerOptions.vue';
import ExamDoubleSelectOptions from '@/components/exam/ExamDoubleSelectOptions.vue';
import ExamQuestionNavigator from '@/components/exam/ExamQuestionNavigator.vue';
import InputError from '@/components/InputError.vue';
import RichContent from '@/components/RichContent.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useExamTimer } from '@/composables/useExamTimer';
import ExamScreenLayout from '@/layouts/exam/ExamScreenLayout.vue';
import type {
    AttemptInfo,
    ExamInfo,
    ExamQuestion,
    ExamSection,
} from '@/types/exam';

defineOptions({
    layout: null,
});

const props = defineProps<{
    exam: ExamInfo;
    attempt: AttemptInfo;
    questions: ExamQuestion[];
    sections: ExamSection[];
    requiresPromoCode?: boolean;
}>();

const activeSection = ref(
    props.sections[0]?.order ??
        props.questions[0]?.section_order ??
        1,
);

const activeQuestionIndex = ref(0);

const { formatted, isUrgent } = useExamTimer({
    startedAt: props.attempt.started_at,
    durationMinutes: props.exam.duration_minutes,
    endsAt: props.exam.ends_at,
});

const selections = ref<Record<number, number[]>>({});
const doubleRowSelections = ref<Record<number, Record<number, number>>>({});

const visibleQuestions = computed(() =>
    props.questions.filter(
        (question) => question.section_order === activeSection.value,
    ),
);

const activeQuestion = computed(
    () => visibleQuestions.value[activeQuestionIndex.value] ?? null,
);

const activeSectionName = computed(
    () =>
        props.sections.find((section) => section.order === activeSection.value)
            ?.name ?? 'Бөлім',
);

const answeredInSection = computed(
    () =>
        visibleQuestions.value.filter((question) =>
            isQuestionAnswered(question),
        ).length,
);

const totalAnswered = computed(
    () =>
        props.questions.filter((question) => isQuestionAnswered(question))
            .length,
);

watch(activeSection, () => {
    activeQuestionIndex.value = 0;
});

watch(visibleQuestions, (questions) => {
    if (activeQuestionIndex.value >= questions.length) {
        activeQuestionIndex.value = Math.max(0, questions.length - 1);
    }
});

function shouldShowContext(question: ExamQuestion): boolean {
    if (! question.context_body) {
        return false;
    }

    const index = visibleQuestions.value.findIndex(
        (item) => item.id === question.id,
    );

    if (index <= 0) {
        return true;
    }

    return (
        visibleQuestions.value[index - 1]?.context_id !== question.context_id
    );
}

function isQuestionAnswered(question: ExamQuestion): boolean {
    if (question.type === 'double') {
        const firstOptions = firstGroupOptions(question);

        if (firstOptions.length === 0) {
            return false;
        }

        const rowSelections = doubleRowSelections.value[question.id] ?? {};

        return firstOptions.every(
            (option) => rowSelections[option.id] !== undefined,
        );
    }

    return (selections.value[question.id] ?? []).length > 0;
}

function firstGroupOptions(question: ExamQuestion) {
    return question.options
        .filter((option) => option.select_group === 'first')
        .sort((left, right) => left.sort_order - right.sort_order);
}

function syncDoubleSelections(question: ExamQuestion): void {
    const rowSelections = doubleRowSelections.value[question.id] ?? {};

    selections.value[question.id] = firstGroupOptions(question)
        .map((option) => rowSelections[option.id])
        .filter((optionId): optionId is number => optionId !== undefined);
}

function selectedForDoubleRow(
    questionId: number,
    firstOptionId: number,
): number | null {
    return doubleRowSelections.value[questionId]?.[firstOptionId] ?? null;
}

function selectDoubleRow(
    question: ExamQuestion,
    firstOptionId: number,
    secondOptionId: number | null,
): void {
    const current = { ...(doubleRowSelections.value[question.id] ?? {}) };

    if (secondOptionId === null) {
        delete current[firstOptionId];
    } else {
        current[firstOptionId] = secondOptionId;
    }

    doubleRowSelections.value[question.id] = current;
    syncDoubleSelections(question);
}

function isQuestionAnsweredById(questionId: number): boolean {
    const question = props.questions.find((item) => item.id === questionId);

    if (! question) {
        return false;
    }

    return isQuestionAnswered(question);
}

function selectQuestion(index: number): void {
    activeQuestionIndex.value = index;
}

function toggleOption(
    questionId: number,
    optionId: number,
    type: ExamQuestion['type'],
    checked: boolean,
): void {
    const current = selections.value[questionId] ?? [];

    if (type === 'single') {
        selections.value[questionId] = checked ? [optionId] : [];
        return;
    }

    selections.value[questionId] = checked
        ? [...current, optionId]
        : current.filter((id) => id !== optionId);
}

function isChecked(questionId: number, optionId: number): boolean {
    return (selections.value[questionId] ?? []).includes(optionId);
}

function selectOption(question: ExamQuestion, optionId: number): void {
    if (question.type === 'multiple') {
        toggleOption(
            question.id,
            optionId,
            question.type,
            ! isChecked(question.id, optionId),
        );
        return;
    }

    toggleOption(question.id, optionId, question.type, true);
}

function buildAnswersPayload(): Array<{
    exam_attempt_question_id: number;
    selected_option_ids: number[];
}> {
    return props.questions.map((question) => ({
        exam_attempt_question_id: question.id,
        selected_option_ids: selections.value[question.id] ?? [],
    }));
}
</script>

<template>
    <ExamScreenLayout
        :sections="props.sections"
        :active-section="activeSection"
        interactive-sections
        show-timer
        show-finish
        :timer="formatted"
        :timer-urgent="isUrgent"
        :header-title="props.exam.title"
        @select-section="activeSection = $event"
    >
        <Head :title="props.exam.title" />

        <Form
            id="exam-submit-form"
            v-bind="ExamAttemptController.submit.form(props.exam.id)"
            :transform="(data) => ({ ...data, answers: buildAnswersPayload() })"
            class="flex min-h-[calc(100dvh-4rem)] flex-1 flex-col bg-gradient-to-b from-muted/20 via-background to-background"
            v-slot="{ errors }"
        >
            <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-5 p-4 lg:p-6">
                <ExamQuestionNavigator
                    :section-name="activeSectionName"
                    :questions="visibleQuestions"
                    :active-index="activeQuestionIndex"
                    :answered-count="answeredInSection"
                    :is-answered="isQuestionAnsweredById"
                    @select="selectQuestion"
                />

                <InputError :message="errors.answers" />

                <div
                    v-if="activeQuestion"
                    class="flex flex-1 flex-col gap-5"
                >
                    <article
                        class="overflow-hidden rounded-2xl border border-border/60 bg-background shadow-sm"
                    >
                        <div
                            class="flex flex-wrap items-center justify-between gap-3 border-b border-border/50 bg-muted/20 px-5 py-4"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex size-10 items-center justify-center rounded-xl bg-primary text-primary-foreground shadow-sm shadow-primary/20"
                                >
                                    <HelpCircle class="size-5" />
                                </div>
                                <div>
                                    <p class="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                                        Сұрақ
                                    </p>
                                    <p class="text-lg font-semibold tabular-nums">
                                        № {{ activeQuestionIndex + 1 }}
                                        <span class="text-sm font-normal text-muted-foreground">
                                            / {{ visibleQuestions.length }}
                                        </span>
                                    </p>
                                </div>
                            </div>

                            <Badge
                                variant="outline"
                                class="rounded-full px-3 py-1"
                            >
                                {{ totalAnswered }} / {{ props.questions.length }} барлығы
                            </Badge>
                        </div>

                        <div class="space-y-6 px-5 py-6">
                            <div
                                v-if="shouldShowContext(activeQuestion)"
                                class="relative overflow-hidden rounded-2xl border border-sky-500/20 bg-gradient-to-br from-sky-500/5 via-background to-background p-5"
                            >
                                <div
                                    class="absolute top-0 left-0 h-full w-1 bg-sky-500"
                                />
                                <div
                                    class="mb-3 flex items-center gap-2 text-sky-700 dark:text-sky-300"
                                >
                                    <FileText class="size-4" />
                                    <p class="text-sm font-semibold">
                                        {{
                                            activeQuestion.context_title ??
                                                'Мәтін'
                                        }}
                                    </p>
                                </div>
                                <RichContent
                                    :content="activeQuestion.context_body ?? ''"
                                />
                            </div>

                            <div
                                class="question-body text-base font-bold leading-relaxed [&_.rich-content]:font-bold [&_.rich-content_p]:font-bold [&_.rich-content_strong]:font-extrabold"
                            >
                                <RichContent :content="activeQuestion.body" />
                            </div>

                            <div class="space-y-3">
                                <p
                                    class="flex items-center gap-2 text-sm font-medium text-muted-foreground"
                                >
                                    <span
                                        class="size-1.5 rounded-full bg-primary"
                                    />
                                    {{
                                        activeQuestion.type === 'double'
                                            ? 'Сопоставьте каждую строку с вариантом из списка'
                                            : 'Жауап нұсқасын таңдаңыз'
                                    }}
                                </p>

                                <ExamDoubleSelectOptions
                                    v-if="activeQuestion.type === 'double'"
                                    :question="activeQuestion"
                                    :selected-for-row="
                                        (firstOptionId) =>
                                            selectedForDoubleRow(
                                                activeQuestion!.id,
                                                firstOptionId,
                                            )
                                    "
                                    @select-row="
                                        (firstOptionId, secondOptionId) =>
                                            selectDoubleRow(
                                                activeQuestion!,
                                                firstOptionId,
                                                secondOptionId,
                                            )
                                    "
                                />

                                <ExamAnswerOptions
                                    v-else
                                    :question="activeQuestion"
                                    :is-checked="
                                        (optionId) =>
                                            isChecked(
                                                activeQuestion!.id,
                                                optionId,
                                            )
                                    "
                                    @select="
                                        selectOption(activeQuestion, $event)
                                    "
                                />
                            </div>
                        </div>
                    </article>

                    <div
                        class="sticky bottom-0 z-10 -mx-4 border-t border-border/60 bg-background/85 px-4 py-4 backdrop-blur-md lg:-mx-6 lg:px-6"
                    >
                        <div
                            class="mx-auto flex max-w-5xl flex-wrap items-center justify-center gap-3 sm:justify-start"
                        >
                            <Button
                                type="button"
                                variant="outline"
                                class="gap-2 rounded-full"
                                :disabled="activeQuestionIndex === 0"
                                @click="
                                    selectQuestion(activeQuestionIndex - 1)
                                "
                            >
                                <ArrowLeft class="size-4" />
                                Артқа
                            </Button>
                            <Button
                                type="button"
                                variant="outline"
                                class="gap-2 rounded-full"
                                :disabled="
                                    activeQuestionIndex >=
                                    visibleQuestions.length - 1
                                "
                                @click="
                                    selectQuestion(activeQuestionIndex + 1)
                                "
                            >
                                Алға
                                <ArrowRight class="size-4" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </Form>
    </ExamScreenLayout>
</template>
