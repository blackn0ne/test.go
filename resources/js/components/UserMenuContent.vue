<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    Building2,
    ClipboardList,
    GraduationCap,
    LogOut,
} from '@lucide/vue';
import { computed, inject } from 'vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { history as examHistory } from '@/routes/exam';
import type { User } from '@/types';

type Props = {
    user: User;
};

defineProps<Props>();

const openDirectionModal = inject<(() => void) | undefined>(
    'openDirectionModal',
    undefined,
);

const page = usePage();
const isAdmin = computed(() => page.props.auth.user?.role === 'admin');
const isStudent = computed(() => page.props.auth.user?.role === 'user');

const schoolName = computed(() => page.props.auth.user?.school?.name ?? null);

const handleLogout = () => {
    router.flushAll();
};
</script>

<template>
    <DropdownMenuLabel class="p-0 font-normal">
        <div class="flex items-center gap-2 px-1 py-1.5 text-left text-sm">
            <UserInfo :user="user" :show-iin="true" :show-email="false" />
        </div>
    </DropdownMenuLabel>
    <DropdownMenuSeparator />
    <DropdownMenuGroup v-if="! isAdmin">
        <DropdownMenuItem
            class="cursor-pointer rounded-lg"
            @click="openDirectionModal?.()"
        >
            <GraduationCap class="mr-2 size-4 shrink-0" />
            <span class="flex min-w-0 flex-col items-start gap-0.5">
                <span class="font-medium">Бағыт</span>
                <span
                    v-if="user.direction"
                    class="truncate text-xs text-muted-foreground"
                >
                    {{ user.direction.name }}
                </span>
                <span v-else class="text-xs text-destructive">
                    Таңдалмаған
                </span>
            </span>
        </DropdownMenuItem>

        <DropdownMenuSeparator />

        <DropdownMenuItem class="cursor-default rounded-lg" @select.prevent>
            <Building2 class="mr-2 size-4 shrink-0" />
            <span class="flex min-w-0 flex-col items-start gap-0.5">
                <span class="font-medium">Мектеп</span>
                <span
                    v-if="schoolName"
                    class="truncate text-xs text-muted-foreground"
                >
                    {{ schoolName }}
                </span>
                <span v-else class="text-xs text-muted-foreground">
                    Көрсетілмеген
                </span>
            </span>
        </DropdownMenuItem>

        <DropdownMenuSeparator v-if="isStudent" />

        <DropdownMenuItem
            v-if="isStudent"
            class="cursor-pointer rounded-lg"
            :as-child="true"
        >
            <Link
                :href="examHistory()"
                class="flex w-full items-center"
            >
                <ClipboardList class="mr-2 size-4 shrink-0" />
                <span class="flex min-w-0 flex-col items-start gap-0.5">
                    <span class="font-medium">Сынақтар</span>
                    <span class="text-xs text-muted-foreground">
                        Менің сынақтарым
                    </span>
                </span>
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator v-if="! isAdmin" />
    <DropdownMenuItem :as-child="true" class="rounded-lg">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 size-4" />
            Шығу
        </Link>
    </DropdownMenuItem>
</template>
