<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import { computed, ref } from 'vue';
import ExamResultQuestionCard from '@/components/exam/ExamResultQuestionCard.vue';
import ExamResultScoreHero from '@/components/exam/ExamResultScoreHero.vue';
import { Button } from '@/components/ui/button';
import ExamScreenLayout from '@/layouts/exam/ExamScreenLayout.vue';
import { show as examShow } from '@/routes/exam';
import type {
    ExamResultAttempt,
    ExamResultQuestion,
    ExamSection,
} from '@/types/exam';

defineOptions({
    layout: null,
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
    <ExamScreenLayout
        :sections="props.sections"
        :active-section="activeSection"
        interactive-sections
        :header-title="props.exam.title"
        @select-section="activeSection = $event"
    >
        <Head :title="`${props.exam.title} — нәтиже`" />

        <div
            class="flex min-h-[calc(100dvh-4rem)] flex-1 flex-col bg-gradient-to-b from-muted/20 via-background to-background"
        >
            <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-5 p-4 lg:p-6">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm text-muted-foreground">
                        Нәтиже · {{ activeSectionMeta?.name ?? 'Бөлім' }}
                    </p>

                    <Button as-child variant="outline" class="rounded-xl">
                        <Link :href="examShow()">
                            <ArrowLeft class="size-4" />
                            Басты бетке
                        </Link>
                    </Button>
                </div>

                <ExamResultScoreHero :attempt="props.attempt" />

                <p class="text-sm text-muted-foreground">
                    {{ visibleQuestions.length }} сұрақ · бөлімді сол
                    жақтағы мәзірден ауыстырыңыз
                </p>

                <div class="space-y-4 pb-8">
                    <ExamResultQuestionCard
                        v-for="(question, index) in visibleQuestions"
                        :key="question.id"
                        :question="question"
                        :index="index"
                    />
                </div>
            </div>
        </div>
    </ExamScreenLayout>
</template>
