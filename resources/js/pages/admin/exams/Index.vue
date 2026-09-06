<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import ExamController from '@/actions/App/Http/Controllers/Admin/ExamController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/components/ui/tooltip';
import { create, edit, index } from '@/routes/admin/exams';

type ExamListItem = {
    id: number;
    title: string;
    status: string;
    status_label: string;
    generation_mode: string;
    generation_mode_label: string;
    direction: { id: number; code: string; name: string } | null;
    blueprint: { id: number; name: string } | null;
    subject: { id: number; name: string } | null;
    attempts_count: number;
    starts_at: string | null;
    ends_at: string | null;
    creator: { id: number; name: string };
};

type PaginatedExams = {
    data: ExamListItem[];
    total: number;
};

defineProps<{
    exams: PaginatedExams;
}>();

function formatPeriod(startsAt: string | null, endsAt: string | null): string {
    if (! startsAt && ! endsAt) {
        return '—';
    }

    const format = (value: string) =>
        new Date(value).toLocaleDateString('ru-RU', {
            day: 'numeric',
            month: 'short',
            year: 'numeric',
        });

    if (startsAt && endsAt) {
        return `${format(startsAt)} — ${format(endsAt)}`;
    }

    return startsAt ? format(startsAt) : format(endsAt!);
}

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Экзамены', href: index() }],
    },
});
</script>

<template>
    <Head title="Экзамены" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 lg:p-6">
        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Экзамены"
                description="ЕНТ с автогенерацией 120 вопросов по направлению"
            />
            <Button as-child>
                <Link :href="create()">Создать экзамен</Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Список экзаменов</CardTitle>
            </CardHeader>
            <CardContent>
                <TooltipProvider>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr
                                    class="border-b text-left text-muted-foreground"
                                >
                                    <th class="pb-3 pr-4 font-medium">
                                        Название
                                    </th>
                                    <th class="pb-3 pr-4 font-medium">
                                        Направление
                                    </th>
                                    <th class="pb-3 pr-4 font-medium">
                                        Период
                                    </th>
                                    <th class="pb-3 pr-4 font-medium">
                                        Режим
                                    </th>
                                    <th class="pb-3 pr-4 font-medium">
                                        Статус
                                    </th>
                                    <th class="pb-3 pr-4 font-medium">
                                        Попыток
                                    </th>
                                    <th class="pb-3 pr-4 font-medium">
                                        Автор
                                    </th>
                                    <th class="pb-3 text-right font-medium">
                                        Действия
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="exam in exams.data"
                                    :key="exam.id"
                                    class="border-b last:border-0"
                                >
                                    <td class="py-3 pr-4 font-medium">
                                        {{ exam.title }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <template v-if="exam.direction">
                                            {{ exam.direction.code }}
                                        </template>
                                        <template v-else-if="exam.subject">
                                            {{ exam.subject.name }}
                                        </template>
                                        <span
                                            v-else
                                            class="text-muted-foreground"
                                            >—</span
                                        >
                                    </td>
                                    <td
                                        class="py-3 pr-4 text-muted-foreground"
                                    >
                                        {{
                                            formatPeriod(
                                                exam.starts_at,
                                                exam.ends_at,
                                            )
                                        }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ exam.generation_mode_label }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        <Badge variant="secondary">
                                            {{ exam.status_label }}
                                        </Badge>
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ exam.attempts_count }}
                                    </td>
                                    <td class="py-3 pr-4">
                                        {{ exam.creator.name }}
                                    </td>
                                    <td class="py-3">
                                        <div
                                            class="flex justify-end gap-1"
                                        >
                                            <Tooltip>
                                                <TooltipTrigger as-child>
                                                    <Button
                                                        as-child
                                                        variant="ghost"
                                                        size="icon-sm"
                                                    >
                                                        <Link
                                                            :href="
                                                                edit(exam.id)
                                                            "
                                                        >
                                                            <Pencil
                                                                class="size-4"
                                                            />
                                                            <span
                                                                class="sr-only"
                                                                >Изменить</span
                                                            >
                                                        </Link>
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
                                                            ExamController.destroy.form(
                                                                exam.id,
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
                                                            <span
                                                                class="sr-only"
                                                                >Удалить</span
                                                            >
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
                                <tr v-if="exams.data.length === 0">
                                    <td
                                        colspan="8"
                                        class="py-8 text-center text-muted-foreground"
                                    >
                                        Экзамены ещё не созданы
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </TooltipProvider>
            </CardContent>
        </Card>
    </div>
</template>
