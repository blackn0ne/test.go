<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import RichEditor from '@/components/RichEditor.vue';
import OptionEditor from '@/components/questions/OptionEditor.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import {
    buildDefaultOptions,
    type OptionLabel,
    type OptionLabelSingle,
    type QuestionContextOption,
    type QuestionFormData,
    type QuestionTypeOption,
    type SubjectOption,
    validateQuestionForm,
} from '@/types/questions';

const props = withDefaults(
    defineProps<{
        form: QuestionFormData;
        subjects: SubjectOption[];
        types: QuestionTypeOption[];
        contexts: QuestionContextOption[];
        errors: Record<string, string>;
        processing: boolean;
        submitLabel?: string;
        mode?: 'create' | 'edit';
        embedded?: boolean;
        hideFooter?: boolean;
    }>(),
    {
        mode: 'create',
        embedded: false,
        hideFooter: false,
    },
);

const emit = defineEmits<{
    submit: [];
}>();

const isEdit = computed(() => props.mode === 'edit');
const selectedType = computed(() => props.form.type);

const typeLabel = computed(
    () =>
        props.types.find((type) => type.value === props.form.type)?.label ??
        props.form.type,
);

const clientErrors = ref<Record<string, string>>({});

const mergedErrors = computed(() => ({
    ...clientErrors.value,
    ...props.errors,
}));

const sectionClass = computed(() =>
    props.embedded
        ? 'rounded-lg border bg-card'
        : 'rounded-xl border bg-card shadow-sm',
);

const filteredContexts = computed(() =>
    props.contexts.filter(
        (context) =>
            !props.form.subject_id ||
            context.subject_id === Number(props.form.subject_id),
    ),
);

watch(
    () => props.form.type,
    (type, previousType) => {
        if (isEdit.value || previousType === undefined || type === previousType) {
            return;
        }

        props.form.options = buildDefaultOptions(type);
    },
);

watch(
    () => props.form.subject_id,
    () => {
        if (
            props.form.context_mode === 'existing' &&
            props.form.context_id &&
            !filteredContexts.value.some(
                (context) => context.id === Number(props.form.context_id),
            )
        ) {
            props.form.context_id = '';
        }
    },
);

watch(
    () => [
        props.form.body,
        props.form.double_first_prompt,
        props.form.double_second_prompt,
        props.form.options,
        props.form.subject_id,
        props.form.context_mode,
        props.form.context_body,
    ],
    () => {
        clientErrors.value = {};
    },
    { deep: true },
);

function optionsForGroup(group: 'first' | 'second' | null) {
    return props.form.options.filter(
        (option) => option.select_group === group,
    );
}

function setSingleCorrect(label: OptionLabelSingle): void {
    props.form.options.forEach((option) => {
        option.is_correct = option.label === label;
    });
}

function setGroupCorrect(
    group: 'first' | 'second',
    label: OptionLabelSingle,
): void {
    props.form.options.forEach((option) => {
        if (option.select_group === group) {
            option.is_correct = option.label === label;
        }
    });
}

function groupTitle(group: 'first' | 'second'): string {
    return group === 'first' ? 'Селект 1' : 'Селект 2';
}

function groupPromptField(group: 'first' | 'second'): 'double_first_prompt' | 'double_second_prompt' {
    return group === 'first' ? 'double_first_prompt' : 'double_second_prompt';
}

function toggleMultipleCorrect(label: OptionLabel, checked: boolean): void {
    const option = props.form.options.find((item) => item.label === label);

    if (option) {
        option.is_correct = checked;
    }
}

function handleSubmit(): void {
    const validationErrors = validateQuestionForm(props.form);

    if (Object.keys(validationErrors).length > 0) {
        clientErrors.value = validationErrors;
        return;
    }

    clientErrors.value = {};
    emit('submit');
}

defineExpose({
    submit: handleSubmit,
});
</script>

<template>
    <div
        :class="
            embedded
                ? 'flex flex-col gap-4'
                : 'flex flex-col gap-6 pb-24'
        "
    >
        <section :class="[sectionClass, embedded ? 'p-4' : 'p-4 shadow-sm lg:p-5']">
            <div
                class="grid gap-4 sm:grid-cols-[minmax(0,1fr)_minmax(0,3fr)] sm:items-end"
            >
                <div class="grid gap-1.5">
                    <Label for="subject_id" class="text-xs text-muted-foreground">
                        Предмет
                    </Label>
                    <select
                        id="subject_id"
                        v-model="form.subject_id"
                        required
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option value="" disabled>Выберите предмет</option>
                        <option
                            v-for="subject in subjects"
                            :key="subject.id"
                            :value="subject.id"
                        >
                            {{ subject.name }}
                        </option>
                    </select>
                    <InputError :message="mergedErrors.subject_id" />
                </div>

                <div class="grid gap-1.5">
                    <Label class="text-xs text-muted-foreground">
                        Тип вопроса
                    </Label>
                    <div v-if="isEdit">
                        <Badge variant="secondary" class="h-9 rounded-md px-3 text-sm font-normal">
                            {{ typeLabel }}
                        </Badge>
                    </div>
                    <div
                        v-else
                        class="inline-flex w-full rounded-md border bg-muted/30 p-0.5"
                        role="radiogroup"
                        aria-label="Тип вопроса"
                    >
                        <button
                            v-for="type in types"
                            :key="type.value"
                            type="button"
                            role="radio"
                            :aria-checked="form.type === type.value"
                            class="flex-1 rounded-sm px-2 py-1.5 text-xs font-medium transition-colors sm:text-sm"
                            :class="
                                cn(
                                    form.type === type.value
                                        ? 'bg-background text-foreground shadow-sm'
                                        : 'text-muted-foreground hover:text-foreground',
                                )
                            "
                            @click="form.type = type.value"
                        >
                            {{ type.label }}
                        </button>
                    </div>
                    <InputError :message="mergedErrors.type" />
                </div>
            </div>
        </section>

        <section :class="[sectionClass, 'p-4']">
            <div class="mb-3">
                <h2 class="text-sm font-medium">Контекст</h2>
                <p class="mt-0.5 text-xs text-muted-foreground">
                    Общий текст для нескольких вопросов (чтение, задача на
                    схему). Ctrl+V — вставка скриншота.
                </p>
            </div>

            <div class="grid gap-3">
                <select
                    v-model="form.context_mode"
                    class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option value="none">Без контекста</option>
                    <option value="existing">Привязать к существующему</option>
                    <option value="new">Создать новый контекст</option>
                </select>

                <div
                    v-if="form.context_mode === 'existing'"
                    class="grid gap-2"
                >
                    <select
                        v-model="form.context_id"
                        class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option value="" disabled>Выберите контекст</option>
                        <option
                            v-for="context in filteredContexts"
                            :key="context.id"
                            :value="context.id"
                        >
                            {{ context.label }}
                        </option>
                    </select>
                    <InputError :message="mergedErrors.context_id" />
                    <p
                        v-if="form.subject_id && filteredContexts.length === 0"
                        class="text-xs text-muted-foreground"
                    >
                        Нет контекстов для этого предмета — создайте новый.
                    </p>
                </div>

                <div v-if="form.context_mode === 'new'" class="grid gap-3">
                    <div class="grid gap-1.5">
                        <Label class="text-xs text-muted-foreground">
                            Заголовок (необязательно)
                        </Label>
                        <input
                            v-model="form.context_title"
                            type="text"
                            placeholder="Например: Текст A"
                            class="flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        />
                    </div>
                    <RichEditor
                        v-model="form.context_body"
                        placeholder="Текст контекста для группы вопросов..."
                    />
                    <InputError :message="mergedErrors.context_body" />
                </div>
            </div>
        </section>

        <section :class="sectionClass">
            <div
                class="border-b px-4 py-2.5"
                :class="embedded ? '' : 'lg:px-5 lg:py-3'"
            >
                <h2 class="text-sm font-medium">Условие вопроса</h2>
            </div>
            <div class="p-4" :class="embedded ? '' : 'lg:p-5'">
                <RichEditor
                    v-model="form.body"
                    placeholder="Введите условие вопроса..."
                />
                <InputError class="mt-2" :message="mergedErrors.body" />
            </div>
        </section>

        <section
            v-if="selectedType !== 'double'"
            :class="sectionClass"
        >
            <div
                class="border-b px-4 py-2.5"
                :class="embedded ? '' : 'lg:px-5 lg:py-3'"
            >
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="text-sm font-medium">Варианты ответов</h2>
                    <span class="text-xs text-muted-foreground">
                        {{
                            selectedType === 'single'
                                ? 'Один правильный'
                                : 'A–F, несколько правильных'
                        }}
                    </span>
                </div>
                <InputError class="mt-1.5" :message="mergedErrors.options" />
            </div>

            <div class="divide-y">
                <div
                    v-for="option in optionsForGroup(null)"
                    :key="option.label"
                    class="flex items-center gap-2.5 px-4 py-2"
                    :class="embedded ? '' : 'lg:px-5 lg:py-2.5'"
                    :data-correct="option.is_correct || undefined"
                >
                    <span
                        class="flex size-6 shrink-0 items-center justify-center rounded border text-[11px] font-semibold tabular-nums"
                        :class="
                            option.is_correct
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-border bg-muted/50 text-muted-foreground'
                        "
                    >
                        {{ option.label }}
                    </span>

                    <label
                        v-if="selectedType === 'single'"
                        class="flex shrink-0 cursor-pointer items-center"
                        :title="'Правильный: ' + option.label"
                    >
                        <input
                            type="radio"
                            name="single_correct"
                            class="size-3.5 accent-primary"
                            :checked="option.is_correct"
                            @change="setSingleCorrect(option.label)"
                        />
                    </label>

                    <label
                        v-else
                        class="flex shrink-0 cursor-pointer items-center"
                        :title="'Правильный: ' + option.label"
                    >
                        <Checkbox
                            class="size-3.5"
                            :model-value="option.is_correct"
                            @update:model-value="
                                (checked) =>
                                    toggleMultipleCorrect(
                                        option.label,
                                        checked === true,
                                    )
                            "
                        />
                    </label>

                    <div class="min-w-0 flex-1">
                        <OptionEditor
                            v-model="option.content"
                            placeholder="Текст варианта..."
                        />
                        <InputError
                            class="mt-0.5"
                            :message="
                                mergedErrors[
                                    `options.${option.sort_order}.content`
                                ]
                            "
                        />
                    </div>
                </div>
            </div>
        </section>

        <section v-else class="grid gap-3 sm:grid-cols-2">
            <div
                v-for="group in ['first', 'second'] as const"
                :key="group"
                :class="sectionClass"
            >
                <div class="border-b px-4 py-2.5">
                    <div class="flex items-center justify-between gap-2">
                        <h2 class="text-sm font-medium">
                            {{ groupTitle(group) }}
                        </h2>
                        <span class="text-xs text-muted-foreground">
                            Заголовок + варианты A–D
                        </span>
                    </div>
                </div>

                <div class="space-y-3 border-b px-4 py-3">
                    <Label class="text-xs text-muted-foreground">
                        Заголовок селекта
                    </Label>
                    <RichEditor
                        v-model="form[groupPromptField(group)]"
                        placeholder="Текст перед выпадающим списком..."
                    />
                    <InputError :message="mergedErrors[groupPromptField(group)]" />
                </div>

                <div class="divide-y">
                    <div
                        v-for="option in optionsForGroup(group)"
                        :key="`${group}-${option.label}`"
                        class="flex items-center gap-2.5 px-4 py-2"
                    >
                        <span
                            class="flex size-6 shrink-0 items-center justify-center rounded border text-[11px] font-semibold tabular-nums"
                            :class="
                                option.is_correct
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-border bg-muted/50 text-muted-foreground'
                            "
                        >
                            {{ option.label }}
                        </span>

                        <label class="flex shrink-0 cursor-pointer items-center">
                            <input
                                type="radio"
                                :name="`${group}_correct`"
                                class="size-3.5 accent-primary"
                                :checked="option.is_correct"
                                @change="
                                    setGroupCorrect(group, option.label)
                                "
                            />
                        </label>

                        <div class="min-w-0 flex-1">
                            <OptionEditor
                                v-model="option.content"
                                placeholder="Текст варианта..."
                            />
                            <InputError
                                class="mt-0.5"
                                :message="
                                    mergedErrors[
                                        `options.${option.sort_order}.content`
                                    ]
                                "
                            />
                        </div>
                    </div>
                </div>
            </div>

            <InputError
                class="sm:col-span-2"
                :message="mergedErrors.options"
            />
        </section>

        <div
            v-if="!hideFooter"
            :class="
                embedded
                    ? 'hidden'
                    : 'sticky bottom-0 z-10 -mx-4 border-t bg-background/95 px-4 py-3 backdrop-blur supports-[backdrop-filter]:bg-background/80 lg:-mx-6 lg:px-6'
            "
        >
            <div class="flex flex-wrap items-center gap-3">
                <Button
                    type="button"
                    :disabled="processing"
                    @click="handleSubmit"
                >
                    {{ submitLabel ?? 'Сохранить' }}
                </Button>
                <slot name="actions" />
            </div>
        </div>
    </div>
</template>

<style scoped>
[data-correct] {
    background-color: color-mix(in oklab, var(--primary) 4%, transparent);
}
</style>
