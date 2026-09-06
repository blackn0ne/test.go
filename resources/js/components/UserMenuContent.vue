<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { Building2, GraduationCap, LogOut } from '@lucide/vue';
import { computed, inject } from 'vue';
import {
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';
import UserInfo from '@/components/UserInfo.vue';
import { logout } from '@/routes';
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
        <DropdownMenuItem class="cursor-default" @select.prevent>
            <Building2 class="mr-2 h-4 w-4" />
            <span class="flex flex-col items-start gap-0.5">
                <span>Мектеп</span>
                <span
                    v-if="schoolName"
                    class="text-xs text-muted-foreground"
                >
                    {{ schoolName }}
                </span>
                <span v-else class="text-xs text-muted-foreground">
                    Не указано
                </span>
            </span>
        </DropdownMenuItem>
    </DropdownMenuGroup>
    <DropdownMenuSeparator v-if="! isAdmin" />
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
