<script setup lang="ts">
import { computed } from 'vue';
import RichContent from '@/components/RichContent.vue';
import { cn } from '@/lib/utils';
import type { ExamQuestion, QuestionOption } from '@/types/exam';

const GROUP_THEMES = [
    {
        label: 'A',
        badge: 'border border-border bg-muted text-muted-foreground',
        border: 'border-border/70',
        bg: 'bg-muted/20',
    },
    {
        label: 'B',
        badge: 'border border-border bg-muted text-muted-foreground',
        border: 'border-border/70',
        bg: 'bg-muted/20',
    },
] as const;

const props = defineProps<{
    question: ExamQuestion;
    selectedForGroup: (group: 'first' | 'second') => number | null;
}>();

const emit = defineEmits<{
    selectGroup: [group: 'first' | 'second', optionId: number | null];
}>();

const groups = computed(() =>
    (['first', 'second'] as const).map((group, index) => ({
        group,
        prompt:
            group === 'first'
                ? props.question.double_first_prompt
                : props.question.double_second_prompt,
        options: optionsForGroup(group),
        theme: GROUP_THEMES[index],
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
    <div class="grid gap-4">
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
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                <div class="flex min-w-0 flex-1 gap-3">
                    <div
                        :class="
                            cn(
                                'flex size-10 shrink-0 items-center justify-center rounded-xl text-sm font-bold shadow-sm',
                                item.theme.badge,
                            )
                        "
                    >
                        {{ item.theme.label }}
                    </div>

                    <div class="min-w-0 flex-1 pt-1">
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
                </div>

                <div class="w-full shrink-0 sm:w-72 lg:w-96">
                    <select
                        :id="`double-select-${question.id}-${item.group}`"
                        class="flex h-11 w-full rounded-xl border border-input bg-background px-3 text-sm shadow-xs outline-none transition-colors focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        :value="selectedForGroup(item.group) ?? ''"
                        :aria-label="`Селект ${item.theme.label}`"
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
        </div>
    </div>
</template>
