<script setup lang="ts">
import RichContent from '@/components/RichContent.vue';
import { Badge } from '@/components/ui/badge';
import {
    type QuestionOptionForm,
    type QuestionTypeValue,
} from '@/types/questions';

type QuestionDetail = {
    id: number;
    type: QuestionTypeValue;
    type_label: string;
    body: string;
    subject?: {
        id: number;
        name: string;
    };
    context?: {
        id: number;
        title: string | null;
        body: string;
    } | null;
    options: QuestionOptionForm[];
};

const props = defineProps<{
    question: QuestionDetail;
}>();

function optionsForGroup(group: 'first' | 'second' | null) {
    return props.question.options.filter(
        (option) => option.select_group === group,
    );
}

function groupTitle(group: 'first' | 'second'): string {
    return group === 'first' ? 'Строки (заголовки)' : 'Варианты для селекта';
}
</script>

<template>
    <div class="flex flex-col gap-5">
        <div class="flex flex-wrap items-center gap-2">
            <Badge variant="outline">{{ question.subject?.name }}</Badge>
            <Badge variant="secondary">{{ question.type_label }}</Badge>
            <Badge
                v-if="question.context"
                variant="outline"
                class="rounded-sm font-normal"
            >
                С контекстом
            </Badge>
        </div>

        <section v-if="question.context">
            <h3 class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                Контекст
                <span v-if="question.context.title" class="normal-case">
                    — {{ question.context.title }}
                </span>
            </h3>
            <div class="rounded-lg border bg-muted/10 p-3">
                <RichContent
                    :content="question.context.body"
                    class="prose prose-sm dark:prose-invert max-w-none"
                />
            </div>
        </section>

        <section>
            <h3 class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                Условие
            </h3>
            <RichContent
                :content="question.body"
                class="prose prose-sm dark:prose-invert max-w-none"
            />
        </section>

        <section v-if="question.type !== 'double'">
            <h3 class="mb-2 text-xs font-medium uppercase tracking-wide text-muted-foreground">
                Варианты ответов
            </h3>
            <div class="divide-y rounded-lg border">
                <div
                    v-for="option in optionsForGroup(null)"
                    :key="option.label"
                    class="flex items-start gap-3 px-3 py-2"
                    :class="option.is_correct ? 'bg-primary/5' : ''"
                >
                    <span
                        class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-md border text-xs font-bold"
                        :class="
                            option.is_correct
                                ? 'border-primary bg-primary text-primary-foreground'
                                : 'border-border bg-muted/40'
                        "
                    >
                        {{ option.label }}
                    </span>
                    <RichContent
                        :content="option.content"
                        class="min-w-0 flex-1 text-sm"
                    />
                </div>
            </div>
        </section>

        <section v-else class="grid gap-4">
            <div
                v-for="group in ['first', 'second'] as const"
                :key="group"
                class="rounded-lg border"
            >
                <div class="border-b px-3 py-2">
                    <h3 class="text-sm font-semibold">
                        {{ groupTitle(group) }}
                    </h3>
                </div>
                <div class="divide-y">
                    <div
                        v-for="option in optionsForGroup(group)"
                        :key="`${group}-${option.label}`"
                        class="flex items-start gap-3 px-3 py-2"
                        :class="option.is_correct ? 'bg-primary/5' : ''"
                    >
                        <span
                            class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-md border text-xs font-bold"
                            :class="
                                option.is_correct
                                    ? 'border-primary bg-primary text-primary-foreground'
                                    : 'border-border bg-muted/40'
                            "
                        >
                            {{ option.label }}
                        </span>
                        <RichContent
                            :content="option.content"
                            class="min-w-0 flex-1 text-sm"
                        />
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
