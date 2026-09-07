<script setup lang="ts">
import { Check } from '@lucide/vue';
import RichContent from '@/components/RichContent.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';
import type { ExamQuestion } from '@/types/exam';

const OPTION_THEMES = [
    {
        badge: 'border border-border bg-muted text-muted-foreground',
        selected:
            'border-primary/80 bg-primary/5 ring-2 ring-primary/20 shadow-sm',
        hover: 'hover:border-muted-foreground/30 hover:bg-muted/40',
    },
] as const;

const optionTheme = OPTION_THEMES[0];

const props = defineProps<{
    question: ExamQuestion;
    isChecked: (optionId: number) => boolean;
}>();

const emit = defineEmits<{
    select: [optionId: number];
}>();
</script>

<template>
    <div class="grid gap-3">
        <button
            v-for="option in question.options"
            :key="option.id"
            type="button"
            :class="
                cn(
                    'group flex w-full items-center gap-4 rounded-2xl border-2 border-border/70 bg-background/80 p-4 text-left transition-all duration-200',
                    optionTheme.hover,
                    isChecked(option.id) && optionTheme.selected,
                )
            "
            @click="emit('select', option.id)"
        >
            <div
                :class="
                    cn(
                        'flex size-11 shrink-0 items-center justify-center rounded-xl text-base font-bold shadow-sm transition-transform duration-200 group-hover:scale-105',
                        optionTheme.badge,
                    )
                "
            >
                {{ option.label }}
            </div>

            <div class="flex min-w-0 flex-1 items-center">
                <RichContent
                    v-if="option.content"
                    :content="option.content"
                    compact
                />
                <p v-else class="text-sm text-muted-foreground">
                    {{ option.label }}
                </p>
            </div>

            <div class="flex shrink-0 items-center">
                <Checkbox
                    v-if="question.type === 'multiple'"
                    :model-value="isChecked(option.id)"
                    class="pointer-events-none size-5"
                />
                <div
                    v-else
                    :class="
                        cn(
                            'flex size-6 items-center justify-center rounded-full border-2 transition-colors',
                            isChecked(option.id)
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-muted-foreground/30 bg-background',
                        )
                    "
                >
                    <Check
                        v-if="isChecked(option.id)"
                        class="size-3.5"
                        stroke-width="3"
                    />
                </div>
            </div>
        </button>
    </div>
</template>

<style scoped>
:deep(.rich-content-compact) {
    display: flex;
    align-items: center;
}

:deep(.rich-content-compact p) {
    line-height: 1.4;
}
</style>
