<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { create, index } from '@/routes/admin/promo-codes';

type PromoCodeItem = {
    id: number;
    code: string;
    is_redeemed: boolean;
};

type BatchItem = {
    id: number;
    year: number;
    month: number;
    coupons_per_student: number;
    students_count: number;
    total_codes: number;
    redeemed_count: number;
    school: { id: number; name: string };
    creator: { id: number; name: string };
    created_at: string;
    codes: PromoCodeItem[];
};

defineProps<{
    batches: BatchItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Промокоды', href: index() }],
    },
});

const monthNames = [
    'Январь',
    'Февраль',
    'Март',
    'Апрель',
    'Май',
    'Июнь',
    'Июль',
    'Август',
    'Сентябрь',
    'Октябрь',
    'Ноябрь',
    'Декабрь',
];

function monthLabel(month: number): string {
    return monthNames[month - 1] ?? String(month);
}
</script>

<template>
    <Head title="Промокоды" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 lg:p-6">
        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Промокоды"
                description="Доступ к экзаменам по школам, месяцам и годам"
            />
            <Button as-child>
                <Link :href="create()">Сгенерировать</Link>
            </Button>
        </div>

        <div v-if="batches.length === 0" class="text-sm text-muted-foreground">
            Промокоды ещё не генерировались.
        </div>

        <div v-else class="space-y-4">
            <Card v-for="batch in batches" :key="batch.id">
                <CardHeader>
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <CardTitle class="text-base">
                                {{ batch.school.name }}
                            </CardTitle>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ monthLabel(batch.month) }} {{ batch.year }} ·
                                {{ batch.coupons_per_student }} на студента ·
                                {{ batch.students_count }} студентов
                            </p>
                        </div>
                        <Badge variant="secondary">
                            {{ batch.redeemed_count }} / {{ batch.total_codes }}
                            использовано
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <div class="flex flex-wrap gap-2">
                        <Badge
                            v-for="code in batch.codes"
                            :key="code.id"
                            :variant="code.is_redeemed ? 'outline' : 'default'"
                            class="font-mono tracking-widest"
                        >
                            {{ code.code }}
                        </Badge>
                    </div>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
