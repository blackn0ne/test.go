<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, ref } from 'vue';
import ExamResultQuestionCard from '@/components/exam/ExamResultQuestionCard.vue';
import ExamResultScoreHero from '@/components/exam/ExamResultScoreHero.vue';
import { Button } from '@/components/ui/button';
import { formatExamScore } from '@/lib/examScores';
import { cn } from '@/lib/utils';
import { show as examShow } from '@/routes/exam';
import type {
    ExamResultAttempt,
    ExamResultQuestion,
    ExamSection,
} from '@/types/exam';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Нәтиже',
            },
        ],
    },
});

const props = defineProps<{
    exam: { id: number; title: string };
    attempt: ExamResultAttempt;
    sections: ExamSection[];
    questions: ExamResultQuestion[];
}>();

const activeSection = ref(
    props.sections[0]?.order ?? props.questions[0]?.section_order ?? 1,
);

const activeSectionMeta = computed(
    () =>
        props.sections.find((section) => section.order === activeSection.value)
        ?? null,
);

const visibleQuestions = computed(() =>
    props.questions.filter(
        (question) => question.section_order === activeSection.value,
    ),
);
</script>

<template>
    <Head :title="`${props.exam.title} — нәтиже`" />

    <div class="flex flex-1 flex-col gap-5 p-4 lg:p-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="min-w-0 space-y-1">
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ props.exam.title }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    Әр бөлім бойынша жауаптар мен дұрыс шешімдер
                </p>
            </div>

            <Button as-child variant="outline" class="rounded-xl">
                <Link :href="examShow()">
                    <ArrowLeft class="size-4" />
                    Басты бетке
                </Link>
            </Button>
        </div>

        <ExamResultScoreHero :attempt="props.attempt" />

        <div class="flex gap-2 overflow-x-auto pb-1">
            <button
                v-for="section in props.sections"
                :key="section.order"
                type="button"
                :class="
                    cn(
                        'shrink-0 rounded-xl border px-4 py-2.5 text-left transition-colors',
                        activeSection === section.order
                            ? 'border-primary bg-primary/10 text-foreground'
                            : 'border-border/60 bg-background hover:bg-muted/30',
                    )
                "
                @click="activeSection = section.order"
            >
                <p class="max-w-[12rem] truncate text-sm font-semibold">
                    {{ section.name }}
                </p>
                <p class="mt-0.5 text-xs tabular-nums text-muted-foreground">
                    {{ formatExamScore(section.score) }} /
                    {{ formatExamScore(section.max_score) }} балл
                </p>
            </button>
        </div>

        <div class="space-y-1">
            <h2 class="text-lg font-semibold">
                {{ activeSectionMeta?.name ?? 'Бөлім' }}
            </h2>
            <p class="text-sm text-muted-foreground">
                {{ visibleQuestions.length }} сұрақ
            </p>
        </div>

        <div class="space-y-4 pb-4">
            <ExamResultQuestionCard
                v-for="(question, index) in visibleQuestions"
                :key="question.id"
                :question="question"
                :index="index"
            />
        </div>
    </div>
</template>
