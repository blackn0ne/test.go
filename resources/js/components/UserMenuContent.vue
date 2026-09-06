<script setup lang="ts">
import { Form, Link, router, usePage } from '@inertiajs/vue3';
import { GraduationCap, LogOut, Settings } from '@lucide/vue';
import { inject } from 'vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
import { edit } from '@/routes/profile';
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
const isAdmin = () => page.props.auth.user?.role === 'admin';

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
    <DropdownMenuGroup v-if="! isAdmin()">
        <DropdownMenuItem
            class="cursor-pointer"
            @click="openDirectionModal?.()"
        >
            <GraduationCap class="mr-2 h-4 w-4" />
            <span class="flex flex-col items-start gap-0.5">
                <span>Направление</span>
                <span
                    v-if="user.direction"
                    class="text-xs text-muted-foreground"
                >
                    {{ user.direction.code }} — {{ user.direction.name }}
                </span>
                <span v-else class="text-xs text-destructive">
                    Не выбрано
                </span>
            </span>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator v-if="! isAdmin()" />
    <DropdownMenuGroup>
        <DropdownMenuItem :as-child="true">
            <Link class="block w-full cursor-pointer" :href="edit()" prefetch>
                <Settings class="mr-2 h-4 w-4" />
                Настройки
            </Link>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator />
    <DropdownMenuItem :as-child="true">
        <Link
            class="block w-full cursor-pointer"
            :href="logout()"
            @click="handleLogout"
            as="button"
            data-test="logout-button"
        >
            <LogOut class="mr-2 h-4 w-4" />
            Выйти
        </Link>
    </DropdownMenuItem>
</template>
