<script setup lang="ts">
import { computed } from 'vue';
import RichContent from '@/components/RichContent.vue';
import { cn } from '@/lib/utils';
import type { ExamQuestion, QuestionOption } from '@/types/exam';

const ROW_THEMES = [
    {
        badge: 'bg-sky-500 text-white',
        border: 'border-sky-500/20',
        bg: 'bg-sky-500/5',
    },
    {
        badge: 'bg-emerald-500 text-white',
        border: 'border-emerald-500/20',
        bg: 'bg-emerald-500/5',
    },
    {
        badge: 'bg-amber-500 text-white',
        border: 'border-amber-500/20',
        bg: 'bg-amber-500/5',
    },
    {
        badge: 'bg-violet-500 text-white',
        border: 'border-violet-500/20',
        bg: 'bg-violet-500/5',
    },
] as const;

const props = defineProps<{
    question: ExamQuestion;
    selectedForRow: (firstOptionId: number) => number | null;
}>();

const emit = defineEmits<{
    selectRow: [firstOptionId: number, secondOptionId: number | null];
}>();

const promptRows = computed(() =>
    optionsForGroup('first').sort(
        (left, right) => left.sort_order - right.sort_order,
    ),
);

const choiceOptions = computed(() =>
    optionsForGroup('second').sort(
        (left, right) => left.sort_order - right.sort_order,
    ),
);

function optionsForGroup(group: 'first' | 'second'): QuestionOption[] {
    return props.question.options.filter(
        (option) => option.select_group === group,
    );
}

function themeForIndex(index: number) {
    return ROW_THEMES[index % ROW_THEMES.length];
}

function optionPlainText(option: QuestionOption): string {
    if (typeof document === 'undefined') {
        return option.content.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').trim()
            || option.label;
    }

    const element = document.createElement('div');
    element.innerHTML = option.content;

    return (element.textContent ?? '').replace(/\s+/g, ' ').trim() || option.label;
}

function handleSelect(firstOptionId: number, value: string): void {
    emit(
        'selectRow',
        firstOptionId,
        value === '' ? null : Number(value),
    );
}
</script>

<template>
    <div class="grid gap-4">
        <div
            v-for="(row, index) in promptRows"
            :key="row.id"
            :class="
                cn(
                    'overflow-hidden rounded-2xl border-2 p-4 transition-colors',
                    themeForIndex(index).border,
                    themeForIndex(index).bg,
                    selectedForRow(row.id) !== null && 'ring-2 ring-primary/15',
                )
            "
        >
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start">
                <div class="flex min-w-0 flex-1 gap-3">
                    <div
                        :class="
                            cn(
                                'flex size-10 shrink-0 items-center justify-center rounded-xl text-sm font-bold shadow-sm',
                                themeForIndex(index).badge,
                            )
                        "
                    >
                        {{ row.label }}
                    </div>

                    <div class="min-w-0 flex-1 pt-1">
                        <RichContent
                            v-if="row.content"
                            :content="row.content"
                            compact
                            class="text-sm font-medium leading-relaxed"
                        />
                        <p
                            v-else
                            class="text-sm text-muted-foreground italic"
                        >
                            Заголовок строки не заполнен
                        </p>
                    </div>
                </div>

                <div class="w-full shrink-0 sm:w-64">
                    <label
                        class="mb-1.5 block text-xs font-medium tracking-wide text-muted-foreground uppercase"
                        :for="`double-select-${question.id}-${row.id}`"
                    >
                        Таңдаңыз
                    </label>
                    <select
                        :id="`double-select-${question.id}-${row.id}`"
                        class="flex h-11 w-full rounded-xl border border-input bg-background px-3 text-sm shadow-xs outline-none transition-colors focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        :value="selectedForRow(row.id) ?? ''"
                        @change="
                            handleSelect(
                                row.id,
                                ($event.target as HTMLSelectElement).value,
                            )
                        "
                    >
                        <option value="" disabled>
                            Выберите вариант...
                        </option>
                        <option
                            v-for="choice in choiceOptions"
                            :key="choice.id"
                            :value="choice.id"
                        >
                            {{ choice.label }}. {{ optionPlainText(choice) }}
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</template>
