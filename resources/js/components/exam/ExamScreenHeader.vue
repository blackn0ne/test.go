<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { Clock3, Flag, GraduationCap } from '@lucide/vue';
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
    showFinish?: boolean;
    finishFormId?: string;
    lobbyMode?: boolean;
};

withDefaults(defineProps<Props>(), {
    title: 'ЕНТ',
    timer: null,
    timerUrgent: false,
    showTimer: false,
    showFinish: false,
    finishFormId: 'exam-submit-form',
    lobbyMode: false,
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
        class="flex h-16 shrink-0 items-center gap-3 px-4 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-6"
        :class="
            cn(
                lobbyMode
                    ? 'border-b border-white/20 bg-gradient-to-r from-sky-400/15 via-violet-400/10 to-emerald-400/15 backdrop-blur-xl'
                    : 'border-b border-border/60 bg-background/80 backdrop-blur-md',
            )
        "
    >
        <SidebarTrigger class="-ml-1 shrink-0" />

        <div class="flex min-w-0 flex-1 items-center">
            <h1
                class="truncate text-sm font-medium md:text-base"
                :class="lobbyMode ? 'text-foreground/90' : 'font-semibold'"
            >
                {{ title }}
            </h1>
        </div>

        <div v-if="showTimer && timer" class="flex items-center gap-2">
            <div
                class="flex items-center gap-2 rounded-full border px-3.5 py-2 text-sm font-semibold tabular-nums shadow-sm"
                :class="
                    cn(
                        timerUrgent
                            ? 'border-orange-500/50 bg-orange-500/15 text-orange-700 dark:text-orange-300'
                            : 'border-amber-400/60 bg-amber-400/15 text-amber-800 dark:text-amber-200',
                    )
                "
                data-test="exam-timer"
            >
                <Clock3
                    class="size-4 shrink-0"
                    :class="timerUrgent ? 'animate-pulse text-orange-600' : 'text-amber-600'"
                />
                <span>{{ timer }}</span>
            </div>

            <Button
                v-if="showFinish"
                type="submit"
                :form="finishFormId"
                variant="destructive"
                size="sm"
                class="gap-1.5 rounded-full px-3 shadow-sm"
                data-test="exam-finish-button"
            >
                <Flag class="size-4" />
                <span class="hidden sm:inline">Аяқтау</span>
            </Button>
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
