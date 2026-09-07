<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, BarChart3 } from 'lucide-vue';
import { computed, ref } from 'vue';
import ExamResultQuestionCard from '@/components/exam/ExamResultQuestionCard.vue';
import ExamResultScoreHero from '@/components/exam/ExamResultScoreHero.vue';
import { Button } from '@/components/ui/button';
import ExamScreenLayout from '@/layouts/exam/ExamScreenLayout.vue';
import { cn } from '@/lib/utils';
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
    () => props.sections.find((section) => section.order === activeSection.value) ?? null,
);

const visibleQuestions = computed(() =>
    props.questions.filter(
        (question) => question.section_order === activeSection.value,
    ),
);

const sectionStats = computed(() =>
    props.sections.map((section) => {
        const percent =
            (section.max_score ?? 0) > 0
                ? Math.round(((section.score ?? 0) / (section.max_score ?? 1)) * 100)
                : 0;

        return {
            ...section,
            percent,
        };
    }),
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
        <Head title="Экзамен нәтижесі" />

        <div
            class="flex min-h-[calc(100dvh-4rem)] flex-1 flex-col bg-gradient-to-b from-muted/20 via-background to-background"
        >
            <div class="mx-auto flex w-full max-w-5xl flex-1 flex-col gap-5 p-4 lg:p-6">
                <ExamResultScoreHero
                    :attempt="props.attempt"
                    :exam-title="props.exam.title"
                />

                <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    <button
                        v-for="section in sectionStats"
                        :key="section.order"
                        type="button"
                        :class="
                            cn(
                                'rounded-2xl border p-4 text-left transition-all',
                                activeSection === section.order
                                    ? 'border-primary/50 bg-primary/5 shadow-sm ring-2 ring-primary/15'
                                    : 'border-border/60 bg-background/80 hover:border-border hover:bg-muted/20',
                            )
                        "
                        @click="activeSection = section.order"
                    >
                        <div class="mb-3 flex items-start justify-between gap-2">
                            <p class="line-clamp-2 text-sm font-semibold leading-snug">
                                {{ section.name }}
                            </p>
                            <BarChart3 class="size-4 shrink-0 text-muted-foreground" />
                        </div>
                        <p class="text-2xl font-black tabular-nums">
                            {{ section.score ?? 0 }}
                            <span class="text-sm font-semibold text-muted-foreground">
                                / {{ section.max_score ?? 0 }}
                            </span>
                        </p>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-sky-500 to-violet-500 transition-all duration-500"
                                :style="{ width: `${section.percent}%` }"
                            />
                        </div>
                    </button>
                </section>

                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-bold">
                            {{ activeSectionMeta?.name ?? 'Бөлім' }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            {{ visibleQuestions.length }} сұрақ ·
                            {{ activeSectionMeta?.score ?? 0 }} /
                            {{ activeSectionMeta?.max_score ?? 0 }} балл
                        </p>
                    </div>

                    <Button as-child variant="outline" class="rounded-xl">
                        <Link :href="examShow()">
                            <ArrowLeft class="size-4" />
                            Басты бетке
                        </Link>
                    </Button>
                </div>

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
