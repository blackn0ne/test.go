<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed, provide, ref, watch } from 'vue';
import AppContent from '@/components/AppContent.vue';
import AppShell from '@/components/AppShell.vue';
import DirectionSelectModal from '@/components/DirectionSelectModal.vue';
import ExamScreenHeader from '@/components/exam/ExamScreenHeader.vue';
import ExamSidebar from '@/components/exam/ExamSidebar.vue';
import { Toaster } from '@/components/ui/sonner';
import type { ExamSection } from '@/types/exam';

type Props = {
    sections?: ExamSection[];
    activeSection?: number | null;
    interactiveSections?: boolean;
    headerTitle?: string;
    timer?: string | null;
    timerUrgent?: boolean;
    showTimer?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    sections: () => [],
    activeSection: null,
    interactiveSections: false,
    headerTitle: 'ЕНТ',
    timer: null,
    timerUrgent: false,
    showTimer: false,
});

const emit = defineEmits<{
    selectSection: [order: number];
}>();

const page = usePage();
const directionModalOpen = ref(false);

const mustSelectDirection = computed(
    () => page.props.auth.user?.must_select_direction ?? false,
);

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
        <ExamSidebar
            :sections="props.sections"
            :active-section="props.activeSection"
            :interactive="props.interactiveSections"
            @select-section="emit('selectSection', $event)"
        />
        <AppContent variant="sidebar" class="overflow-x-hidden">
            <ExamScreenHeader
                :title="props.headerTitle"
                :timer="props.timer"
                :timer-urgent="props.timerUrgent"
                :show-timer="props.showTimer"
            />
            <slot />
        </AppContent>
        <Toaster />
        <DirectionSelectModal
            v-model:open="directionModalOpen"
            :required="mustSelectDirection"
        />
    </AppShell>
</template>
