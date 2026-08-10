<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { Eye, Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import QuestionController from '@/actions/App/Http/Controllers/Admin/QuestionController';
import Heading from '@/components/Heading.vue';
import QuestionSheet, {
    type QuestionSheetState,
} from '@/components/questions/QuestionSheet.vue';
import RichContent from '@/components/RichContent.vue';
import TablePagination from '@/components/TablePagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { index } from '@/routes/admin/questions';
import {
    type QuestionContextOption,
    type QuestionTypeOption,
    type SubjectOption,
} from '@/types/questions';

type QuestionListItem = {
    id: number;
    type: 'single' | 'multiple' | 'double';
    type_label: string;
    body: string;
    subject: {
        id: number;
        name: string;
    };
    created_at: string;
};

type PaginatedQuestions = {
    data: QuestionListItem[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
};

const props = defineProps<{
    questions: PaginatedQuestions;
    filters: {
        subject_id?: number | string | null;
        type?: string | null;
        search?: string | null;
    };
    subjects: SubjectOption[];
    types: QuestionTypeOption[];
    contexts: QuestionContextOption[];
    sheet: QuestionSheetState | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Вопросы',
                href: index(),
            },
        ],
    },
});

function filterParams(extra: Record<string, string | number | undefined> = {}) {
    return {
        subject_id: props.filters.subject_id || undefined,
        type: props.filters.type || undefined,
        search: props.filters.search || undefined,
        ...extra,
    };
}

function applyFilters(event: Event): void {
    const form = event.target as HTMLFormElement;
    const data = new FormData(form);

    router.get(
        index().url,
        {
            subject_id: data.get('subject_id') || undefined,
            type: data.get('type') || undefined,
            search: data.get('search') || undefined,
        },
        { preserveState: true, replace: true },
    );
}

function openSheet(
    mode: QuestionSheetState['mode'],
    questionId?: number,
): void {
    router.get(
        index().url,
        filterParams({
            sheet: mode,
            question: questionId,
        }),
        {
            preserveState: true,
            preserveScroll: true,
            only: ['sheet'],
        },
    );
}

function closeSheet(): void {
    router.get(index().url, filterParams(), {
        preserveState: true,
        replace: true,
        preserveScroll: true,
        only: ['sheet'],
    });
}

function editFromShow(questionId: number): void {
    openSheet('edit', questionId);
}

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-background px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';
</script>

<template>
    <Head title="Вопросы" />

    <TooltipProvider>
        <div class="flex h-full flex-1 flex-col gap-5 p-4 lg:p-6">
            <div class="flex items-start justify-between gap-4">
                <Heading
                    title="Вопросы"
                    description="Банк вопросов для экзаменов"
                />
                <Button type="button" size="sm" @click="openSheet('create')">
                    <Plus class="size-4" />
                    Создать
                </Button>
            </div>

            <section class="overflow-hidden rounded-lg border bg-card">
                <form
                    class="grid gap-3 border-b bg-muted/20 p-4 md:grid-cols-[1fr_160px_160px_auto] md:items-center"
                    @submit.prevent="applyFilters"
                >
                    <div class="relative">
                        <Search
                            class="pointer-events-none absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground"
                        />
                        <Input
                            id="search"
                            name="search"
                            :default-value="filters.search ?? ''"
                            placeholder="Поиск по тексту..."
                            class="h-9 bg-background pl-9"
                        />
                    </div>

                    <select
                        id="subject_id"
                        name="subject_id"
                        :class="selectClass"
                        :value="filters.subject_id ?? ''"
                    >
                        <option value="">Все предметы</option>
                        <option
                            v-for="subject in subjects"
                            :key="subject.id"
                            :value="subject.id"
                        >
                            {{ subject.name }}
                        </option>
                    </select>

                    <select
                        id="type"
                        name="type"
                        :class="selectClass"
                        :value="filters.type ?? ''"
                    >
                        <option value="">Все типы</option>
                        <option value="single">Один ответ</option>
                        <option value="multiple">Несколько ответов</option>
                        <option value="double">Два селекта</option>
                    </select>

                    <Button type="submit" variant="secondary" class="h-9">
                        Применить
                    </Button>
                </form>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b bg-muted/10 text-left text-xs text-muted-foreground">
                                <th class="px-4 py-2.5 font-medium">
                                    Вопрос
                                </th>
                                <th class="hidden w-36 px-4 py-2.5 font-medium lg:table-cell">
                                    Предмет
                                </th>
                                <th class="hidden w-40 px-4 py-2.5 font-medium md:table-cell">
                                    Тип
                                </th>
                                <th class="w-28 px-4 py-2.5 text-right font-medium">
                                    Действия
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="question in questions.data"
                                :key="question.id"
                                class="border-b transition-colors last:border-0 hover:bg-muted/20"
                            >
                                <td class="max-w-xl px-4 py-3">
                                    <div
                                        class="max-h-16 overflow-hidden text-foreground"
                                    >
                                        <RichContent
                                            :content="question.body"
                                            compact
                                        />
                                    </div>
                                    <div
                                        class="mt-1.5 flex flex-wrap items-center gap-2 md:hidden"
                                    >
                                        <span class="text-xs text-muted-foreground">
                                            {{ question.subject.name }}
                                        </span>
                                        <Badge
                                            variant="outline"
                                            class="rounded-sm px-1.5 py-0 text-[11px] font-normal"
                                        >
                                            {{ question.type_label }}
                                        </Badge>
                                    </div>
                                </td>
                                <td
                                    class="hidden px-4 py-3 text-muted-foreground lg:table-cell"
                                >
                                    {{ question.subject.name }}
                                </td>
                                <td class="hidden px-4 py-3 md:table-cell">
                                    <Badge
                                        variant="outline"
                                        class="rounded-sm px-1.5 py-0 text-[11px] font-normal"
                                    >
                                        {{ question.type_label }}
                                    </Badge>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex justify-end gap-0.5">
                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    @click="
                                                        openSheet(
                                                            'show',
                                                            question.id,
                                                        )
                                                    "
                                                >
                                                    <Eye class="size-4" />
                                                    <span class="sr-only">
                                                        Показать
                                                    </span>
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                Показать
                                            </TooltipContent>
                                        </Tooltip>

                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon-sm"
                                                    @click="
                                                        openSheet(
                                                            'edit',
                                                            question.id,
                                                        )
                                                    "
                                                >
                                                    <Pencil class="size-4" />
                                                    <span class="sr-only">
                                                        Изменить
                                                    </span>
                                                </Button>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                Изменить
                                            </TooltipContent>
                                        </Tooltip>

                                        <Tooltip>
                                            <TooltipTrigger as-child>
                                                <Form
                                                    v-bind="
                                                        QuestionController.destroy.form(
                                                            question.id,
                                                        )
                                                    "
                                                    class="inline"
                                                >
                                                    <Button
                                                        type="submit"
                                                        variant="ghost"
                                                        size="icon-sm"
                                                        class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                                    >
                                                        <Trash2
                                                            class="size-4"
                                                        />
                                                        <span class="sr-only">
                                                            Удалить
                                                        </span>
                                                    </Button>
                                                </Form>
                                            </TooltipTrigger>
                                            <TooltipContent>
                                                Удалить
                                            </TooltipContent>
                                        </Tooltip>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="questions.data.length === 0">
                                <td
                                    colspan="4"
                                    class="px-4 py-12 text-center text-sm text-muted-foreground"
                                >
                                    Вопросы не найдены
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="questions.total > 0" class="px-4 pb-4">
                    <TablePagination
                        :current-page="questions.current_page"
                        :last-page="questions.last_page"
                        :per-page="questions.per_page"
                        :total="questions.total"
                        :links="questions.links"
                    />
                </div>
            </section>

            <QuestionSheet
                :sheet="sheet"
                :subjects="subjects"
                :types="types"
                :contexts="contexts"
                @close="closeSheet"
                @edit="editFromShow"
            />
        </div>
    </TooltipProvider>
</template>
