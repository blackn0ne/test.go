<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import QuestionController from '@/actions/App/Http/Controllers/Admin/QuestionController';
import QuestionForm from '@/components/questions/QuestionForm.vue';
import QuestionShowPanel from '@/components/questions/QuestionShowPanel.vue';
import { Button } from '@/components/ui/button';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
} from '@/components/ui/sheet';
import {
    emptyQuestionForm,
    questionFormFromQuestion,
    type QuestionContextOption,
    type QuestionFormData,
    type QuestionOptionForm,
    type QuestionTypeOption,
    type QuestionTypeValue,
    type SubjectOption,
} from '@/types/questions';

type SheetQuestion = {
    id: number;
    subject_id: number;
    type: QuestionTypeValue;
    type_label: string;
    body: string;
    double_first_prompt?: string | null;
    double_second_prompt?: string | null;
    subject?: {
        id: number;
        name: string;
    };
    context?: {
        id: number;
        title: string | null;
        body: string;
    } | null;
    options: QuestionOptionForm[];
};

export type QuestionSheetState = {
    mode: 'create' | 'edit' | 'show';
    question?: SheetQuestion;
};

const props = defineProps<{
    sheet: QuestionSheetState | null;
    subjects: SubjectOption[];
    types: QuestionTypeOption[];
    contexts: QuestionContextOption[];
}>();

const emit = defineEmits<{
    close: [];
    edit: [questionId: number];
}>();

const formRef = ref<InstanceType<typeof QuestionForm> | null>(null);

const isOpen = computed(() => props.sheet !== null);

const isFormMode = computed(
    () => props.sheet?.mode === 'create' || props.sheet?.mode === 'edit',
);

const sheetTitle = computed(() => {
    switch (props.sheet?.mode) {
        case 'create':
            return 'Создать вопрос';
        case 'edit':
            return 'Изменить вопрос';
        case 'show':
            return 'Просмотр вопроса';
        default:
            return '';
    }
});

const sheetDescription = computed(() => {
    switch (props.sheet?.mode) {
        case 'create':
            return 'Добавьте вопрос с формулами и вариантами ответов';
        case 'edit':
            return 'Измените предмет, условие и варианты ответов';
        case 'show':
            return 'Просмотр условия и вариантов ответов';
        default:
            return '';
    }
});

const submitLabel = computed(() =>
    props.sheet?.mode === 'create' ? 'Создать' : 'Сохранить',
);

const form = useForm<QuestionFormData>(emptyQuestionForm());

watch(
    () => props.sheet,
    (sheet) => {
        if (!sheet) {
            return;
        }

        if (sheet.mode === 'create') {
            form.defaults(emptyQuestionForm()).reset();
            form.clearErrors();

            return;
        }

        if (sheet.question) {
            form.defaults(questionFormFromQuestion(sheet.question)).reset();
            form.clearErrors();
        }
    },
    { immediate: true },
);

function handleOpenChange(open: boolean): void {
    if (!open) {
        emit('close');
    }
}

function submit(): void {
    if (props.sheet?.mode === 'create') {
        form.post(QuestionController.store.url(), {
            preserveScroll: true,
        });

        return;
    }

    if (props.sheet?.mode === 'edit' && props.sheet.question) {
        form.put(QuestionController.update.url(props.sheet.question.id), {
            preserveScroll: true,
        });
    }
}

function submitForm(): void {
    formRef.value?.submit();
}
</script>

<template>
    <Sheet :open="isOpen" @update:open="handleOpenChange">
        <SheetContent
            side="right"
            class="flex w-full max-w-[800px] flex-col gap-0 p-0 sm:max-w-[800px]"
        >
            <SheetHeader class="shrink-0 space-y-1 border-b px-6 py-4 text-left">
                <SheetTitle class="text-base">{{ sheetTitle }}</SheetTitle>
                <SheetDescription class="text-xs">
                    {{ sheetDescription }}
                </SheetDescription>
            </SheetHeader>

            <div class="min-h-0 flex-1 overflow-y-auto px-6 py-4">
                <QuestionShowPanel
                    v-if="sheet?.mode === 'show' && sheet.question"
                    :question="sheet.question"
                />

                <QuestionForm
                    v-else-if="isFormMode"
                    ref="formRef"
                    :mode="sheet!.mode"
                    embedded
                    hide-footer
                    :form="form"
                    :subjects="subjects"
                    :types="types"
                    :contexts="contexts"
                    :errors="form.errors"
                    :processing="form.processing"
                    :submit-label="submitLabel"
                    @submit="submit"
                />
            </div>

            <div
                v-if="isFormMode"
                class="flex shrink-0 items-center gap-2 border-t bg-background px-6 py-3"
            >
                <Button
                    type="button"
                    :disabled="form.processing"
                    @click="submitForm"
                >
                    {{ submitLabel }}
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    @click="emit('close')"
                >
                    Отмена
                </Button>
            </div>

            <div
                v-else-if="sheet?.mode === 'show' && sheet.question"
                class="flex shrink-0 items-center gap-2 border-t bg-background px-6 py-3"
            >
                <Button
                    type="button"
                    @click="emit('edit', sheet.question!.id)"
                >
                    Изменить
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    @click="emit('close')"
                >
                    Закрыть
                </Button>
            </div>
        </SheetContent>
    </Sheet>
</template>
