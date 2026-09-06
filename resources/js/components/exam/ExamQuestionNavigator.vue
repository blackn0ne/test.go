<script setup lang="ts">
import { BookOpen, CheckCircle2 } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
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
        answered && active && 'bg-emerald-600 text-white shadow-md shadow-emerald-600/30 ring-2 ring-emerald-300 ring-offset-2 ring-offset-background',
        ! answered && active && 'bg-primary text-primary-foreground shadow-md shadow-primary/20 ring-2 ring-primary/30 ring-offset-2 ring-offset-background',
        ! answered && ! active && 'border border-border/80 bg-background text-foreground hover:border-primary/30 hover:bg-primary/5',
    );
}
</script>

<template>
    <div
        class="overflow-hidden rounded-2xl border border-border/60 bg-gradient-to-br from-muted/30 via-background to-primary/5 shadow-sm"
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

            <div
                class="relative -mx-1"
            >
                <div
                    class="flex gap-2 overflow-x-auto px-1 pb-1 [-ms-overflow-style:none] [scrollbar-width:thin] [&::-webkit-scrollbar]:h-1.5 [&::-webkit-scrollbar-thumb]:rounded-full [&::-webkit-scrollbar-thumb]:bg-border"
                >
                    <button
                        v-for="(question, index) in questions"
                        :key="question.id"
                        type="button"
                        :class="slotClass(index, question.id)"
                        :aria-label="`Сұрақ ${index + 1}`"
                        :aria-current="activeIndex === index ? 'true' : undefined"
                        @click="emit('select', index)"
                    >
                        <CheckCircle2
                            v-if="isAnswered(question.id) && activeIndex !== index"
                            class="absolute -top-1 -right-1 size-3.5 rounded-full bg-background text-emerald-500"
                        />
                        {{ index + 1 }}
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap gap-3 text-[11px] text-muted-foreground">
                <span class="inline-flex items-center gap-1.5">
                    <span
                        class="size-2.5 rounded-md border border-border bg-background"
                    />
                    Жауапсыз
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="size-2.5 rounded-md bg-emerald-500" />
                    Жауап берілді
                </span>
                <span class="inline-flex items-center gap-1.5">
                    <span
                        class="size-2.5 rounded-md bg-primary ring-2 ring-primary/30 ring-offset-1 ring-offset-background"
                    />
                    Ағымдағы
                </span>
            </div>
        </div>
    </div>
</template>
