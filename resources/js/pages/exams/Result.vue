<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { dashboard } from '@/routes';

type AttemptInfo = {
    id: number;
    status: string;
    total_score: number | null;
    max_score: number | null;
    submitted_at: string | null;
};

defineProps<{
    exam: { id: number; title: string };
    attempt: AttemptInfo;
}>();

defineOptions({ layout: { breadcrumbs: [] } });
</script>

<template>
    <Head title="Результат экзамена" />

    <div class="mx-auto flex h-full max-w-xl flex-1 flex-col gap-6 p-4 lg:p-6">
        <Heading
            title="Экзамен завершён"
            :description="exam.title"
        />

        <Card>
            <CardHeader>
                <CardTitle>Ваш результат</CardTitle>
            </CardHeader>
            <CardContent class="space-y-2 text-lg">
                <p>
                    Баллы:
                    <strong>{{ attempt.total_score ?? 0 }}</strong>
                    из
                    <strong>{{ attempt.max_score ?? 0 }}</strong>
                </p>
                <p class="text-sm text-muted-foreground">
                    Правильные ответы проверяются только на сервере.
                </p>
            </CardContent>
        </Card>

        <Button as-child variant="outline">
            <Link :href="dashboard()">На главную</Link>
        </Button>
    </div>
</template>
