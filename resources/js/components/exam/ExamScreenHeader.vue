<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Clock3, GraduationCap, Sparkles } from '@lucide/vue';
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
        class="flex h-16 shrink-0 items-center gap-3 border-b border-border/60 bg-background/80 px-4 backdrop-blur-md transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-6"
    >
        <SidebarTrigger class="-ml-1 shrink-0" />

        <div class="flex min-w-0 flex-1 items-center gap-2.5">
            <div
                class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
            >
                <Sparkles class="size-4" />
            </div>
            <h1 class="truncate text-sm font-semibold md:text-base">
                {{ title }}
            </h1>
        </div>

        <div
            v-if="showTimer && timer"
            class="flex items-center gap-2 rounded-full px-3.5 py-2 text-sm font-semibold tabular-nums shadow-sm"
            :class="
                cn(
                    timerUrgent
                        ? 'border border-destructive/30 bg-destructive/10 text-destructive'
                        : 'border border-border/70 bg-muted/50 text-foreground',
                )
            "
            data-test="exam-timer"
        >
            <Clock3
                class="size-4 shrink-0"
                :class="timerUrgent ? 'animate-pulse' : ''"
            />
            <span>{{ timer }}</span>
        </div>

        <Button
            v-if="user"
            type="button"
            variant="outline"
            size="sm"
            class="shrink-0 rounded-full"
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
