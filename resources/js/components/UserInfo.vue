<script setup lang="ts">
import { computed } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';

type Props = {
    user: User;
    showIin?: boolean;
    showEmail?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    showIin: true,
    showEmail: false,
});

const { getInitials } = useInitials();

const showAvatar = computed(
    () => props.user.avatar && props.user.avatar !== '',
);

const secondaryLine = computed(() => {
    if (props.showIin && props.user.iin) {
        return props.user.iin;
    }

    if (props.showEmail) {
        return props.user.email;
    }

    return null;
});
</script>

<template>
    <Avatar class="h-8 w-8 shrink-0 overflow-hidden rounded-lg">
        <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
        <AvatarFallback class="rounded-lg text-black dark:text-white">
            {{ getInitials(user.name) }}
        </AvatarFallback>
    </Avatar>

    <div class="grid min-w-0 flex-1 text-left text-sm leading-tight">
        <span class="truncate font-medium">{{ user.name }}</span>
        <span
            v-if="secondaryLine"
            class="truncate text-xs text-muted-foreground"
        >
            {{ secondaryLine }}
        </span>
    </div>
</template>
