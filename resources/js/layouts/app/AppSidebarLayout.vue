<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, provide, ref, watch } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import AppSidebar from '@/components/AppSidebar.vue';
import AppSidebarHeader from '@/components/AppSidebarHeader.vue';
import DirectionSelectModal from '@/components/DirectionSelectModal.vue';
import { Toaster } from '@/components/ui/sonner';
import type { BreadcrumbItem } from '@/types';

type Props = {
    breadcrumbs?: BreadcrumbItem[];
};

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});

const page = usePage();
const directionModalOpen = ref(false);

const mustSelectDirection = computed(
    () => page.props.auth.user?.must_select_direction ?? false,
);

const showDirectionModal = computed(() => {
    const user = page.props.auth.user;

    return user !== null && user.role !== 'admin';
});

watch(
    mustSelectDirection,
    (required) => {
        if (required) {
            directionModalOpen.value = true;
        }
    },
    { immediate: true },
);

provide('openDirectionModal', () => {
    directionModalOpen.value = true;
});
</script>

<template>
    <AppShell variant="sidebar">
        <AppSidebar />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <AppSidebarHeader :breadcrumbs="breadcrumbs" />
            <slot />
        </AppContent>
        <Toaster />
        <DirectionSelectModal
            v-if="showDirectionModal"
            v-model:open="directionModalOpen"
            :required="mustSelectDirection"
        />
    </AppShell>
</template>
