<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import ExamAttemptController from '@/actions/App/Http/Controllers/ExamAttemptController';
import InputError from '@/components/InputError.vue';
import RichContent from '@/components/RichContent.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
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

const answeredInSection = computed(() => {
    return visibleQuestions.value.filter(
        (question) => (selections.value[question.id] ?? []).length > 0,
    ).length;
});

function shouldShowContext(question: ExamQuestion, index: number): boolean {
    if (! question.context_body) {
        return false;
    }

    if (index === 0) {
        return true;
    }

    const previous = visibleQuestions.value[index - 1];

    return previous.context_id !== question.context_id;
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

function buildAnswersPayload(): Array<{
    exam_attempt_question_id: number;
    selected_option_ids: number[];
}> {
    return props.questions.map((question) => ({
        exam_attempt_question_id: question.id,
        selected_option_ids: selections.value[question.id] ?? [],
    }));
}

const activeSectionName = computed(
    () =>
        props.sections.find((section) => section.order === activeSection.value)
            ?.name ?? 'Бөлім',
);
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
            class="flex flex-1 flex-col gap-6 p-4 lg:p-6"
            v-slot="{ errors, processing }"
        >
            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-xl border bg-muted/20 px-4 py-3"
            >
                <div>
                    <p class="text-sm font-medium">{{ activeSectionName }}</p>
                    <p class="text-xs text-muted-foreground">
                        Отвечено {{ answeredInSection }} из
                        {{ visibleQuestions.length }}
                    </p>
                </div>
                <p class="text-xs text-muted-foreground">
                    Всего вопросов: {{ props.questions.length }}
                </p>
            </div>

            <InputError :message="errors.answers" />

            <div class="space-y-6">
                <Card
                    v-for="(question, index) in visibleQuestions"
                    :key="question.id"
                >
                    <CardHeader>
                        <CardTitle class="text-base">
                            Сұрақ {{ question.sort_order + 1 }}
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div
                            v-if="shouldShowContext(question, index)"
                            class="rounded-lg border bg-muted/20 p-4"
                        >
                            <p
                                v-if="question.context_title"
                                class="mb-2 text-sm font-medium"
                            >
                                {{ question.context_title }}
                            </p>
                            <RichContent
                                :content="question.context_body ?? ''"
                            />
                        </div>

                        <RichContent :content="question.body" />

                        <div class="grid gap-3">
                            <div
                                v-for="option in question.options"
                                :key="option.id"
                                class="flex items-start gap-3 rounded-lg border p-3 transition-colors hover:bg-muted/30"
                            >
                                <Checkbox
                                    v-if="question.type === 'multiple'"
                                    :model-value="
                                        isChecked(question.id, option.id)
                                    "
                                    @update:model-value="
                                        (checked) =>
                                            toggleOption(
                                                question.id,
                                                option.id,
                                                question.type,
                                                checked === true,
                                            )
                                    "
                                />
                                <input
                                    v-else
                                    type="radio"
                                    :name="`question-${question.id}`"
                                    class="mt-1 size-4 accent-primary"
                                    :checked="
                                        isChecked(question.id, option.id)
                                    "
                                    @change="
                                        toggleOption(
                                            question.id,
                                            option.id,
                                            question.type,
                                            true,
                                        )
                                    "
                                />
                                <Label class="flex-1 cursor-pointer">
                                    <span class="mr-2 font-semibold">{{
                                        option.label
                                    }}</span>
                                    <RichContent
                                        :content="option.content"
                                        tag="span"
                                    />
                                </Label>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="sticky bottom-4 flex justify-end pb-2">
                <Button type="submit" size="lg" :disabled="processing">
                    Завершить экзамен
                </Button>
            </div>
        </Form>
    </ExamScreenLayout>
</template>
