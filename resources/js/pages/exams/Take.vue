<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    ArrowLeft,
    ArrowRight,
    FileText,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import ExamAttemptController from '@/actions/App/Http/Controllers/ExamAttemptController';
import ExamAnswerOptions from '@/components/exam/ExamAnswerOptions.vue';
import ExamDoubleSelectOptions from '@/components/exam/ExamDoubleSelectOptions.vue';
import ExamQuestionNavigator from '@/components/exam/ExamQuestionNavigator.vue';
import InputError from '@/components/InputError.vue';
import RichContent from '@/components/RichContent.vue';
import { Button } from '@/components/ui/button';
import { useExamTimer } from '@/composables/useExamTimer';
import {
    buildSelectionsFromSavedAnswers,
    useExamAnswerPersistence,
} from '@/composables/useExamAnswerPersistence';
import ExamScreenLayout from '@/layouts/exam/ExamScreenLayout.vue';
import type {
    AttemptInfo,
    ExamInfo,
    ExamQuestion,
    ExamSection,
    SavedAnswer,
} from '@/types/exam';
import { MULTIPLE_MAX_SELECTIONS } from '@/types/exam';

defineOptions({
    layout: null,
});

const props = defineProps<{
    exam: ExamInfo;
    attempt: AttemptInfo;
    questions: ExamQuestion[];
    sections: ExamSection[];
    savedAnswers?: SavedAnswer[];
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

const selections = ref<Record<number, number[]>>(
    buildSelectionsFromSavedAnswers(props.savedAnswers ?? []),
);

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

function optionsForGroup(
    question: ExamQuestion,
    group: 'first' | 'second',
) {
    return question.options.filter(
        (option) => option.select_group === group,
    );
}

function selectedForDoubleGroup(
    questionId: number,
    group: 'first' | 'second',
): number | null {
    const question = props.questions.find((item) => item.id === questionId);

    if (! question) {
        return null;
    }

    const groupOptionIds = optionsForGroup(question, group).map(
        (option) => option.id,
    );

    return (
        (selections.value[questionId] ?? []).find((optionId) =>
            groupOptionIds.includes(optionId),
        ) ?? null
    );
}

function selectDoubleGroup(
    question: ExamQuestion,
    group: 'first' | 'second',
    optionId: number | null,
): void {
    const groupOptionIds = optionsForGroup(question, group).map(
        (option) => option.id,
    );
    const current = (selections.value[question.id] ?? []).filter(
        (id) => ! groupOptionIds.includes(id),
    );

    if (optionId !== null) {
        current.push(optionId);
    }

    selections.value[question.id] = current;
}

function isQuestionAnswered(question: ExamQuestion): boolean {
    if (question.type === 'double') {
        return (
            selectedForDoubleGroup(question.id, 'first') !== null
            && selectedForDoubleGroup(question.id, 'second') !== null
        );
    }

    return (selections.value[question.id] ?? []).length > 0;
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

    if (checked && current.length >= MULTIPLE_MAX_SELECTIONS) {
        return;
    }

    selections.value[questionId] = checked
        ? [...current, optionId]
        : current.filter((id) => id !== optionId);
}

function canSelectOption(question: ExamQuestion, optionId: number): boolean {
    if (question.type !== 'multiple') {
        return true;
    }

    if (isChecked(question.id, optionId)) {
        return true;
    }

    return (selections.value[question.id] ?? []).length < MULTIPLE_MAX_SELECTIONS;
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

useExamAnswerPersistence(props.exam.id, selections, buildAnswersPayload);
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
            <div class="mx-auto flex w-full flex-1 flex-col gap-5 p-4 lg:p-6">
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
                                <ExamDoubleSelectOptions
                                    v-if="activeQuestion.type === 'double'"
                                    :question="activeQuestion"
                                    :selected-for-group="
                                        (group) =>
                                            selectedForDoubleGroup(
                                                activeQuestion!.id,
                                                group,
                                            )
                                    "
                                    @select-group="
                                        (group, optionId) =>
                                            selectDoubleGroup(
                                                activeQuestion!,
                                                group,
                                                optionId,
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
                                    :can-select="
                                        (optionId) =>
                                            canSelectOption(
                                                activeQuestion!,
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
                        <div class="grid w-full grid-cols-2 gap-3">
                            <Button
                                type="button"
                                variant="outline"
                                class="h-12 w-full gap-2 rounded-xl border-border/70 text-base font-semibold shadow-sm"
                                :disabled="activeQuestionIndex === 0"
                                @click="
                                    selectQuestion(activeQuestionIndex - 1)
                                "
                            >
                                <ArrowLeft class="size-5" />
                                Артқа
                            </Button>
                            <Button
                                type="button"
                                class="h-12 w-full gap-2 rounded-xl text-base font-semibold shadow-sm"
                                :disabled="
                                    activeQuestionIndex >=
                                    visibleQuestions.length - 1
                                "
                                @click="
                                    selectQuestion(activeQuestionIndex + 1)
                                "
                            >
                                Алға
                                <ArrowRight class="size-5" />
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </Form>
    </ExamScreenLayout>
</template>
