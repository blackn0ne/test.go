<script setup lang="ts">
import { computed } from 'vue';
import RichContent from '@/components/RichContent.vue';
import { cn } from '@/lib/utils';
import type { ExamQuestion, QuestionOption } from '@/types/exam';

const GROUP_THEMES = {
    first: {
        badge: 'bg-sky-500 text-white',
        border: 'border-sky-500/20',
        bg: 'bg-sky-500/5',
    },
    second: {
        badge: 'bg-emerald-500 text-white',
        border: 'border-emerald-500/20',
        bg: 'bg-emerald-500/5',
    },
} as const;

const props = defineProps<{
    question: ExamQuestion;
    selectedForGroup: (group: 'first' | 'second') => number | null;
}>();

const emit = defineEmits<{
    selectGroup: [group: 'first' | 'second', optionId: number | null];
}>();

const groups = computed(() =>
    (['first', 'second'] as const).map((group) => ({
        group,
        prompt:
            group === 'first'
                ? props.question.double_first_prompt
                : props.question.double_second_prompt,
        options: optionsForGroup(group),
        theme: GROUP_THEMES[group],
    })),
);

function optionsForGroup(group: 'first' | 'second'): QuestionOption[] {
    return props.question.options
        .filter((option) => option.select_group === group)
        .sort((left, right) => left.sort_order - right.sort_order);
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

function handleSelect(group: 'first' | 'second', value: string): void {
    emit('selectGroup', group, value === '' ? null : Number(value));
}
</script>

<template>
    <div class="grid gap-4 sm:grid-cols-2">
        <div
            v-for="item in groups"
            :key="item.group"
            :class="
                cn(
                    'overflow-hidden rounded-2xl border-2 p-4 transition-colors',
                    item.theme.border,
                    item.theme.bg,
                    selectedForGroup(item.group) !== null && 'ring-2 ring-primary/15',
                )
            "
        >
            <div class="mb-4 min-w-0">
                <RichContent
                    v-if="item.prompt"
                    :content="item.prompt"
                    compact
                    class="text-sm font-medium leading-relaxed"
                />
                <p
                    v-else
                    class="text-sm text-muted-foreground italic"
                >
                    Заголовок селекта не заполнен
                </p>
            </div>

            <label
                class="mb-1.5 block text-xs font-medium tracking-wide text-muted-foreground uppercase"
                :for="`double-select-${question.id}-${item.group}`"
            >
                Таңдаңыз
            </label>
            <select
                :id="`double-select-${question.id}-${item.group}`"
                class="flex h-11 w-full rounded-xl border border-input bg-background px-3 text-sm shadow-xs outline-none transition-colors focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                :value="selectedForGroup(item.group) ?? ''"
                @change="
                    handleSelect(
                        item.group,
                        ($event.target as HTMLSelectElement).value,
                    )
                "
            >
                <option value="" disabled>
                    Выберите вариант...
                </option>
                <option
                    v-for="option in item.options"
                    :key="option.id"
                    :value="option.id"
                >
                    {{ option.label }}. {{ optionPlainText(option) }}
                </option>
            </select>
        </div>
    </div>
</template>
