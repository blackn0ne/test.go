<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { GraduationCap } from '@lucide/vue';
import { computed, inject } from 'vue';
import Breadcrumbs from '@/components/Breadcrumbs.vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import type { BreadcrumbItem, User } from '@/types';

withDefaults(
    defineProps<{
        breadcrumbs?: BreadcrumbItem[];
    }>(),
    {
        breadcrumbs: () => [],
    },
);

const page = usePage();
const user = computed(() => page.props.auth.user as User | null);
const isAdmin = computed(() => user.value?.role === 'admin');

const openDirectionModal = inject<(() => void) | undefined>(
    'openDirectionModal',
    undefined,
);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-2 border-b border-sidebar-border/70 px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4"
    >
        <div class="flex min-w-0 flex-1 items-center gap-2">
            <SidebarTrigger class="-ml-1" />
            <template v-if="breadcrumbs && breadcrumbs.length > 0">
                <Breadcrumbs :breadcrumbs="breadcrumbs" />
            </template>
        </div>

        <Button
            v-if="user && ! isAdmin"
            type="button"
            variant="outline"
            size="sm"
            class="shrink-0"
            data-test="direction-header-button"
            @click="openDirectionModal?.()"
        >
            <GraduationCap class="size-4" />
            <span class="max-w-[12rem] truncate">
                {{
                    user.direction
                        ? `${user.direction.code} — ${user.direction.name}`
                        : 'Выбрать направление'
                }}
            </span>
        </Button>
    </header>
</template>
