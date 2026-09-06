<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    Atom,
    BookOpen,
    Calculator,
    LayoutGrid,
} from '@lucide/vue';
import { ref } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavUser from '@/components/NavUser.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarGroup,
    SidebarGroupLabel,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { cn } from '@/lib/utils';
import { show as examShow } from '@/routes/exam';
import type { ExamSection, ExamToolId } from '@/types/exam';

type Props = {
    sections: ExamSection[];
    activeSection?: number | null;
    interactive?: boolean;
};

withDefaults(defineProps<Props>(), {
    activeSection: null,
    interactive: false,
});

const emit = defineEmits<{
    selectSection: [order: number];
}>();

const activeTool = ref<ExamToolId | null>(null);

const tools: Array<{ id: ExamToolId; title: string; icon: typeof Calculator }> =
    [
        { id: 'calculator', title: 'Калькулятор', icon: Calculator },
        { id: 'periodic-table', title: 'Таблица Менделеева', icon: Atom },
        { id: 'instructions', title: 'Инструкция', icon: BookOpen },
    ];

const toolContent: Record<
    ExamToolId,
    { title: string; description: string }
> = {
    calculator: {
        title: 'Калькулятор',
        description:
            'Встроенный калькулятор будет доступен во время экзамена. Пока используйте стандартный калькулятор операционной системы.',
    },
    'periodic-table': {
        title: 'Таблица Менделеева',
        description:
            'Справочник периодической таблицы элементов будет доступен во время экзамена.',
    },
    instructions: {
        title: 'Инструкция',
        description:
            '1. Выберите раздел в меню «Бөлімдер».\n2. Отвечайте на все вопросы раздела.\n3. Следите за таймером в верхней панели.\n4. После завершения всех разделов нажмите «Завершить экзамен».',
    },
};

function openTool(toolId: ExamToolId): void {
    activeTool.value = toolId;
}
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="examShow()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Бөлімдер</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem
                        v-for="section in sections"
                        :key="section.order"
                    >
                        <SidebarMenuButton
                            v-if="interactive"
                            type="button"
                            :is-active="activeSection === section.order"
                            :tooltip="section.name"
                            @click="emit('selectSection', section.order)"
                        >
                            <LayoutGrid />
                            <span class="truncate">{{ section.name }}</span>
                        </SidebarMenuButton>
                        <SidebarMenuButton
                            v-else
                            type="button"
                            :tooltip="section.name"
                            class="cursor-default opacity-80"
                        >
                            <LayoutGrid />
                            <span class="truncate">{{ section.name }}</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>

            <SidebarGroup class="px-2 py-0">
                <SidebarGroupLabel>Инструменты</SidebarGroupLabel>
                <SidebarMenu>
                    <SidebarMenuItem v-for="tool in tools" :key="tool.id">
                        <SidebarMenuButton
                            type="button"
                            :tooltip="tool.title"
                            @click="openTool(tool.id)"
                        >
                            <component :is="tool.icon" />
                            <span>{{ tool.title }}</span>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroup>
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>

    <Dialog
        :open="activeTool !== null"
        @update:open="(value) => ! value && (activeTool = null)"
    >
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>
                    {{ activeTool ? toolContent[activeTool].title : '' }}
                </DialogTitle>
                <DialogDescription
                    :class="
                        cn(
                            activeTool === 'instructions' && 'whitespace-pre-line',
                        )
                    "
                >
                    {{ activeTool ? toolContent[activeTool].description : '' }}
                </DialogDescription>
            </DialogHeader>
            <Button
                type="button"
                variant="secondary"
                @click="activeTool = null"
            >
                Закрыть
            </Button>
        </DialogContent>
    </Dialog>
</template>
