<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import { index as adminReports } from '@/routes/admin/reports';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Отчёты',
                href: adminReports(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Отчёты" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Heading
            title="Отчёты"
            description="Раздел в разработке. Отчёты по тестам настроим позже."
        />

        <div
            class="relative min-h-64 flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border"
        >
            <PlaceholderPattern />
        </div>
    </div>
</template>
