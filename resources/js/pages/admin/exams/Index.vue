<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { create, index } from '@/routes/admin/exams';

type ExamListItem = {
    id: number;
    title: string;
    status: string;
    status_label: string;
    subject: { id: number; name: string };
    questions_count: number;
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
                description="Экзамены с фиксированным набором вопросов и серверной проверкой"
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
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-3 pr-4 font-medium">Название</th>
                                <th class="pb-3 pr-4 font-medium">Предмет</th>
                                <th class="pb-3 pr-4 font-medium">Статус</th>
                                <th class="pb-3 pr-4 font-medium">Вопросов</th>
                                <th class="pb-3 font-medium">Автор</th>
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
                                    {{ exam.subject.name }}
                                </td>
                                <td class="py-3 pr-4">
                                    <Badge variant="secondary">
                                        {{ exam.status_label }}
                                    </Badge>
                                </td>
                                <td class="py-3 pr-4">
                                    {{ exam.questions_count }}
                                </td>
                                <td class="py-3">
                                    {{ exam.creator.name }}
                                </td>
                            </tr>
                            <tr v-if="exams.data.length === 0">
                                <td
                                    colspan="5"
                                    class="py-8 text-center text-muted-foreground"
                                >
                                    Экзамены ещё не созданы
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
