<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Clock3, GraduationCap, LayoutGrid } from '@lucide/vue';
import { computed, inject } from 'vue';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import type { User } from '@/types';

type Props = {
    title?: string;
    timer?: string | null;
    timerUrgent?: boolean;
    showTimer?: boolean;
};

withDefaults(defineProps<Props>(), {
    title: 'ЕНТ',
    timer: null,
    timerUrgent: false,
    showTimer: false,
});

const page = usePage();
const user = computed(() => page.props.auth.user as User | null);

const openDirectionModal = inject<(() => void) | undefined>(
    'openDirectionModal',
    undefined,
);
</script>

<template>
    <header
        class="flex h-16 shrink-0 items-center gap-3 border-b border-sidebar-border/70 px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-6"
    >
        <SidebarTrigger class="-ml-1 shrink-0" />

        <div class="flex min-w-0 flex-1 items-center gap-2">
            <LayoutGrid class="size-4 shrink-0 text-muted-foreground" />
            <h1 class="truncate text-sm font-semibold md:text-base">
                {{ title }}
            </h1>
        </div>

        <div
            v-if="showTimer && timer"
            class="flex items-center gap-2 rounded-full border px-3 py-1.5 text-sm font-medium tabular-nums"
            :class="
                cn(
                    timerUrgent
                        ? 'border-destructive/40 bg-destructive/10 text-destructive'
                        : 'border-border bg-muted/40',
                )
            "
            data-test="exam-timer"
        >
            <Clock3 class="size-4 shrink-0" />
            <span>{{ timer }}</span>
        </div>

        <Button
            v-if="user"
            type="button"
            variant="outline"
            size="sm"
            class="shrink-0"
            data-test="direction-header-button"
            @click="openDirectionModal?.()"
        >
            <GraduationCap class="size-4" />
            <span class="max-w-[10rem] truncate md:max-w-[12rem]">
                {{
                    user.direction
                        ? `${user.direction.code} — ${user.direction.name}`
                        : 'Выбрать направление'
                }}
            </span>
        </Button>
    </header>
</template>
