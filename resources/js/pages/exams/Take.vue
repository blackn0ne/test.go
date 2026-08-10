<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import ExamAttemptController from '@/actions/App/Http/Controllers/ExamAttemptController';
import Heading from '@/components/Heading.vue';
import RichContent from '@/components/RichContent.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { ref } from 'vue';

type ExamInfo = {
    id: number;
    title: string;
    description: string | null;
    duration_minutes: number | null;
    ends_at: string | null;
};

type AttemptInfo = {
    id: number;
    status: string;
    started_at: string;
    max_score: number | null;
};

type QuestionOption = {
    id: number;
    question_option_id: number;
    label: string;
    content: string;
    select_group: string | null;
    sort_order: number;
};

type ExamQuestion = {
    id: number;
    question_id: number;
    type: 'single' | 'multiple' | 'double';
    body: string;
    sort_order: number;
    context_id: number | null;
    context_title: string | null;
    context_body: string | null;
    options: QuestionOption[];
};

function shouldShowContext(question: ExamQuestion, index: number): boolean {
    if (!question.context_body) {
        return false;
    }

    if (index === 0) {
        return true;
    }

    const previous = props.questions[index - 1];

    return previous.context_id !== question.context_id;
}

const props = defineProps<{
    exam: ExamInfo;
    attempt: AttemptInfo;
    questions: ExamQuestion[];
}>();

const selections = ref<Record<number, number[]>>({});

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
</script>

<template>
    <Head :title="exam.title" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 lg:p-6">
        <Heading
            :title="exam.title"
            :description="exam.description ?? 'Ответьте на все вопросы и отправьте работу.'"
        />

        <Form
            v-bind="ExamAttemptController.submit.form(exam.id)"
            :transform="(data) => ({ ...data, answers: buildAnswersPayload() })"
            class="space-y-6"
            v-slot="{ processing }"
        >
            <Card v-for="(question, index) in questions" :key="question.id">
                <CardHeader>
                    <CardTitle class="text-base">
                        Вопрос {{ question.sort_order + 1 }}
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
                        <RichContent :content="question.context_body ?? ''" />
                    </div>

                    <RichContent :content="question.body" />

                    <div class="grid gap-3">
                        <div
                            v-for="option in question.options"
                            :key="option.id"
                            class="flex items-start gap-3 rounded-lg border p-3"
                        >
                            <Checkbox
                                v-if="question.type === 'multiple'"
                                :model-value="isChecked(question.id, option.id)"
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
                                :checked="isChecked(question.id, option.id)"
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
                                <RichContent :content="option.content" tag="span" />
                            </Label>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Button type="submit" size="lg" :disabled="processing">
                Завершить экзамен
            </Button>
        </Form>
    </div>
</template>
