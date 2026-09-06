<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import ExamAttemptController from '@/actions/App/Http/Controllers/ExamAttemptController';
import InputError from '@/components/InputError.vue';
import RichContent from '@/components/RichContent.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { useExamTimer } from '@/composables/useExamTimer';
import ExamScreenLayout from '@/layouts/exam/ExamScreenLayout.vue';
import { cn } from '@/lib/utils';
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
        visibleQuestions.value.filter(
            (question) => (selections.value[question.id] ?? []).length > 0,
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

function isQuestionAnswered(questionId: number): boolean {
    return (selections.value[questionId] ?? []).length > 0;
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

    if (type === 'single' || type === 'double') {
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
        :timer="formatted"
        :timer-urgent="isUrgent"
        :header-title="props.exam.title"
        @select-section="activeSection = $event"
    >
        <Head :title="props.exam.title" />

        <Form
            v-bind="ExamAttemptController.submit.form(props.exam.id)"
            :transform="(data) => ({ ...data, answers: buildAnswersPayload() })"
            class="flex flex-1 flex-col gap-4 p-4 lg:p-6"
            v-slot="{ errors, processing }"
        >
            <div class="space-y-3">
                <div
                    class="flex flex-wrap items-center justify-between gap-2"
                >
                    <p class="text-sm font-semibold">
                        {{ activeSectionName }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Жауап берілді {{ answeredInSection }} /
                        {{ visibleQuestions.length }}
                    </p>
                </div>

                <div
                    class="flex gap-2 overflow-x-auto pb-1 [-ms-overflow-style:none] [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
                >
                    <button
                        v-for="(question, index) in visibleQuestions"
                        :key="question.id"
                        type="button"
                        :class="
                            cn(
                                'flex size-9 shrink-0 items-center justify-center rounded-lg border text-sm font-medium transition-all',
                                isQuestionAnswered(question.id)
                                    ? 'border-emerald-500 bg-emerald-500 text-white hover:bg-emerald-600'
                                    : 'border-border bg-background hover:bg-muted/50',
                                activeQuestionIndex === index &&
                                    'ring-2 ring-primary ring-offset-2 ring-offset-background',
                            )
                        "
                        :aria-label="`Сұрақ ${index + 1}`"
                        :aria-current="
                            activeQuestionIndex === index ? 'true' : undefined
                        "
                        @click="selectQuestion(index)"
                    >
                        {{ index + 1 }}
                    </button>
                </div>
            </div>

            <InputError :message="errors.answers" />

            <div v-if="activeQuestion" class="flex flex-1 flex-col gap-4">
                <div class="space-y-4">
                    <p class="text-sm font-medium text-muted-foreground">
                        Сұрақ {{ activeQuestionIndex + 1 }}
                    </p>

                    <div
                        v-if="shouldShowContext(activeQuestion)"
                        class="rounded-xl bg-muted/30 p-4"
                    >
                        <p
                            v-if="activeQuestion.context_title"
                            class="mb-2 text-sm font-medium"
                        >
                            {{ activeQuestion.context_title }}
                        </p>
                        <RichContent
                            :content="activeQuestion.context_body ?? ''"
                        />
                    </div>

                    <RichContent :content="activeQuestion.body" />

                    <div class="grid gap-2">
                        <button
                            v-for="option in activeQuestion.options"
                            :key="option.id"
                            type="button"
                            :class="
                                cn(
                                    'flex w-full items-start gap-3 rounded-xl border p-4 text-left transition-colors',
                                    isChecked(activeQuestion.id, option.id)
                                        ? 'border-primary bg-primary/5'
                                        : 'border-border bg-background hover:bg-muted/30',
                                )
                            "
                            @click="selectOption(activeQuestion, option.id)"
                        >
                            <Checkbox
                                v-if="activeQuestion.type === 'multiple'"
                                :model-value="
                                    isChecked(activeQuestion.id, option.id)
                                "
                                class="pointer-events-none mt-0.5"
                                @update:model-value="
                                    (checked) =>
                                        toggleOption(
                                            activeQuestion!.id,
                                            option.id,
                                            activeQuestion!.type,
                                            checked === true,
                                        )
                                "
                            />
                            <span
                                v-else
                                :class="
                                    cn(
                                        'mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full border',
                                        isChecked(activeQuestion.id, option.id)
                                            ? 'border-primary bg-primary'
                                            : 'border-muted-foreground/40',
                                    )
                                "
                            >
                                <span
                                    v-if="
                                        isChecked(
                                            activeQuestion.id,
                                            option.id,
                                        )
                                    "
                                    class="size-2 rounded-full bg-primary-foreground"
                                />
                            </span>

                            <span class="min-w-0 flex-1">
                                <Label
                                    class="flex cursor-pointer flex-col gap-1 font-normal"
                                >
                                    <span class="font-semibold">{{
                                        option.label
                                    }}</span>
                                    <RichContent
                                        v-if="option.content"
                                        :content="option.content"
                                        compact
                                    />
                                </Label>
                            </span>
                        </button>
                    </div>
                </div>

                <div
                    class="mt-auto flex flex-wrap items-center justify-between gap-3 pt-4"
                >
                    <div class="flex gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="activeQuestionIndex === 0"
                            @click="selectQuestion(activeQuestionIndex - 1)"
                        >
                            Артқа
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="
                                activeQuestionIndex >=
                                visibleQuestions.length - 1
                            "
                            @click="selectQuestion(activeQuestionIndex + 1)"
                        >
                            Алға
                        </Button>
                    </div>

                    <Button type="submit" size="lg" :disabled="processing">
                        Завершить экзамен
                    </Button>
                </div>
            </div>
        </Form>
    </ExamScreenLayout>
</template>
