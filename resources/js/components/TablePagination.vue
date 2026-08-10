<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    links: PaginationLink[];
}>();

const rangeFrom = computed(() => {
    if (props.total === 0) {
        return 0;
    }

    return (props.currentPage - 1) * props.perPage + 1;
});

const rangeTo = computed(() =>
    Math.min(props.currentPage * props.perPage, props.total),
);

const previousLink = computed(() => props.links.at(0) ?? null);

const nextLink = computed(() => props.links.at(-1) ?? null);

const pageLinks = computed(() =>
    props.links.filter((link) => {
        const label = link.label.replace(/&[^;]+;/g, '').trim();

        return label !== '' && !Number.isNaN(Number(label));
    }),
);
</script>

<template>
    <div
        class="flex flex-col gap-3 border-t px-1 pt-4 sm:flex-row sm:items-center sm:justify-between"
    >
        <p class="text-xs text-muted-foreground">
            <template v-if="total === 0">Нет записей</template>
            <template v-else>
                {{ rangeFrom }}–{{ rangeTo }} из {{ total }}
            </template>
        </p>

        <div class="flex items-center gap-1">
            <Button
                v-if="previousLink?.url"
                as-child
                variant="outline"
                size="icon-sm"
            >
                <Link :href="previousLink.url" preserve-state>
                    <ChevronLeft class="size-4" />
                    <span class="sr-only">Назад</span>
                </Link>
            </Button>
            <Button
                v-else
                variant="outline"
                size="icon-sm"
                disabled
            >
                <ChevronLeft class="size-4" />
                <span class="sr-only">Назад</span>
            </Button>

            <template v-if="lastPage > 1">
                <Button
                    v-for="link in pageLinks"
                    :key="link.label"
                    as-child
                    size="sm"
                    :variant="link.active ? 'default' : 'ghost'"
                    class="min-w-8 px-2"
                >
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-state
                        v-html="link.label"
                    />
                    <span v-else v-html="link.label" />
                </Button>
            </template>

            <span
                v-else-if="total > 0"
                class="px-2 text-xs text-muted-foreground"
            >
                стр. {{ currentPage }}
            </span>

            <Button
                v-if="nextLink?.url"
                as-child
                variant="outline"
                size="icon-sm"
            >
                <Link :href="nextLink.url" preserve-state>
                    <ChevronRight class="size-4" />
                    <span class="sr-only">Вперёд</span>
                </Link>
            </Button>
            <Button
                v-else
                variant="outline"
                size="icon-sm"
                disabled
            >
                <ChevronRight class="size-4" />
                <span class="sr-only">Вперёд</span>
            </Button>
        </div>
    </div>
</template>
