<script setup lang="ts">
import { Check } from '@lucide/vue';
import RichContent from '@/components/RichContent.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { cn } from '@/lib/utils';
import type { ExamQuestion } from '@/types/exam';

const OPTION_THEMES = [
    {
        badge: 'bg-sky-500 text-white shadow-sky-500/30',
        selected:
            'border-sky-500/80 bg-sky-500/8 ring-2 ring-sky-500/20 shadow-sm shadow-sky-500/10',
        hover: 'hover:border-sky-500/40 hover:bg-sky-500/5',
    },
    {
        badge: 'bg-emerald-500 text-white shadow-emerald-500/30',
        selected:
            'border-emerald-500/80 bg-emerald-500/8 ring-2 ring-emerald-500/20 shadow-sm shadow-emerald-500/10',
        hover: 'hover:border-emerald-500/40 hover:bg-emerald-500/5',
    },
    {
        badge: 'bg-amber-500 text-white shadow-amber-500/30',
        selected:
            'border-amber-500/80 bg-amber-500/8 ring-2 ring-amber-500/20 shadow-sm shadow-amber-500/10',
        hover: 'hover:border-amber-500/40 hover:bg-amber-500/5',
    },
    {
        badge: 'bg-violet-500 text-white shadow-violet-500/30',
        selected:
            'border-violet-500/80 bg-violet-500/8 ring-2 ring-violet-500/20 shadow-sm shadow-violet-500/10',
        hover: 'hover:border-violet-500/40 hover:bg-violet-500/5',
    },
    {
        badge: 'bg-rose-500 text-white shadow-rose-500/30',
        selected:
            'border-rose-500/80 bg-rose-500/8 ring-2 ring-rose-500/20 shadow-sm shadow-rose-500/10',
        hover: 'hover:border-rose-500/40 hover:bg-rose-500/5',
    },
] as const;

const props = defineProps<{
    question: ExamQuestion;
    isChecked: (optionId: number) => boolean;
}>();

const emit = defineEmits<{
    select: [optionId: number];
}>();

function themeForIndex(index: number) {
    return OPTION_THEMES[index % OPTION_THEMES.length];
}
</script>

<template>
    <div class="grid gap-3">
        <button
            v-for="(option, index) in question.options"
            :key="option.id"
            type="button"
            :class="
                cn(
                    'group flex w-full items-start gap-4 rounded-2xl border-2 border-border/70 bg-background/80 p-4 text-left transition-all duration-200',
                    themeForIndex(index).hover,
                    isChecked(option.id) && themeForIndex(index).selected,
                )
            "
            @click="emit('select', option.id)"
        >
            <div
                :class="
                    cn(
                        'flex size-11 shrink-0 items-center justify-center rounded-xl text-base font-bold shadow-sm transition-transform duration-200 group-hover:scale-105',
                        themeForIndex(index).badge,
                    )
                "
            >
                {{ option.label }}
            </div>

            <div class="min-w-0 flex-1 pt-0.5">
                <RichContent
                    v-if="option.content"
                    :content="option.content"
                    compact
                />
                <p v-else class="text-sm text-muted-foreground">
                    {{ option.label }}
                </p>
            </div>

            <div class="flex shrink-0 items-center pt-1">
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
