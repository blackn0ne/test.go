<script setup lang="ts">
import { BookOpen } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { useDragScroll } from '@/composables/useDragScroll';
import { cn } from '@/lib/utils';

type QuestionSlot = {
    id: number;
};

const props = defineProps<{
    sectionName: string;
    questions: QuestionSlot[];
    activeIndex: number;
    answeredCount: number;
    isAnswered: (questionId: number) => boolean;
}>();

const emit = defineEmits<{
    select: [index: number];
}>();

const progress = computed(() => {
    if (props.questions.length === 0) {
        return 0;
    }

    return Math.round(
        (props.answeredCount / props.questions.length) * 100,
    );
});

function slotClass(index: number, questionId: number): string {
    const answered = props.isAnswered(questionId);
    const active = props.activeIndex === index;

    return cn(
        'relative flex size-10 shrink-0 items-center justify-center rounded-xl text-sm font-semibold transition-all duration-200',
        answered && ! active && 'bg-emerald-500 text-white shadow-sm shadow-emerald-500/25',
        answered && active && 'bg-emerald-600 text-white shadow-[0_0_0_2px_var(--background),0_0_0_3px_rgb(16,185,129)]',
        ! answered && active && 'bg-primary text-primary-foreground shadow-[0_0_0_2px_var(--background),0_0_0_3px_hsl(var(--primary))]',
        ! answered && ! active && 'border border-border/80 bg-background text-foreground hover:border-primary/30 hover:bg-primary/5',
    );
}

const scrollContainerRef = ref<HTMLElement | null>(null);

useDragScroll(scrollContainerRef);

watch(
    () => props.activeIndex,
    (index) => {
        const container = scrollContainerRef.value;

        if (container === null) {
            return;
        }

        const activeButton = container.querySelector<HTMLElement>(
            `[data-question-index="${index}"]`,
        );

        activeButton?.scrollIntoView({
            behavior: 'smooth',
            block: 'nearest',
            inline: 'center',
        });
    },
);
</script>

<template>
    <div
        class="rounded-2xl border border-border/60 bg-gradient-to-br from-muted/30 via-background to-primary/5 shadow-sm"
    >
        <div class="flex flex-wrap items-start justify-between gap-3 border-b border-border/50 px-4 py-3">
            <div class="flex items-center gap-3">
                <div
                    class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                >
                    <BookOpen class="size-5" />
                </div>
                <div>
                    <p class="font-semibold leading-tight">
                        {{ sectionName }}
                    </p>
                    <p class="text-xs text-muted-foreground">
                        Сұрақтар картасы
                    </p>
                </div>
            </div>

            <Badge
                variant="secondary"
                class="rounded-full px-3 py-1 tabular-nums"
            >
                {{ answeredCount }} / {{ questions.length }}
            </Badge>
        </div>

        <div class="space-y-3 px-4 py-3">
            <div class="flex items-center gap-3">
                <div
                    class="h-2 flex-1 overflow-hidden rounded-full bg-muted/80"
                >
                    <div
                        class="h-full rounded-full bg-gradient-to-r from-emerald-500 via-emerald-400 to-teal-400 transition-all duration-500 ease-out"
                        :style="{ width: `${progress}%` }"
                    />
                </div>
                <span class="text-xs font-medium text-muted-foreground tabular-nums">
                    {{ progress }}%
                </span>
            </div>

            <div class="relative px-1 py-1.5">
                <div
                    ref="scrollContainerRef"
                    class="flex gap-2 overflow-x-auto overflow-y-visible px-1 py-1 touch-pan-x [-ms-overflow-style:none] [scrollbar-width:thin] [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-border"
                >
                    <button
                        v-for="(question, index) in questions"
                        :key="question.id"
                        type="button"
                        :data-question-index="index"
                        :class="slotClass(index, question.id)"
                        :aria-label="`Сұрақ ${index + 1}`"
                        :aria-current="activeIndex === index ? 'true' : undefined"
                        @click="emit('select', index)"
                    >
                        {{ index + 1 }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
