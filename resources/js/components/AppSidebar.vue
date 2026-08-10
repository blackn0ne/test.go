<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    BookMarked,
    ClipboardList,
    CircleHelp,
    LayoutGrid,
    Settings,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as adminDirectories } from '@/routes/admin/directories';
import { index as adminExams } from '@/routes/admin/exams';
import { index as adminQuestions } from '@/routes/admin/questions';
import { index as adminReports } from '@/routes/admin/reports';
import { edit as adminSettings } from '@/routes/admin/settings';
import { index as adminUsers } from '@/routes/admin/users';
import type { NavItem } from '@/types';
import type { UserRole } from '@/types/auth';

const page = usePage();
const isAdmin = computed(
    () => (page.props.auth.user?.role as UserRole | undefined) === 'admin',
);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (isAdmin.value) {
        items.push(
            {
                title: 'Юзеры',
                href: adminUsers(),
                icon: Users,
            },
            {
                title: 'Вопросы',
                href: adminQuestions(),
                icon: CircleHelp,
            },
            {
                title: 'Экзамены',
                href: adminExams(),
                icon: ClipboardList,
            },
            {
                title: 'Отчёты',
                href: adminReports(),
                icon: BarChart3,
            },
            {
                title: 'Настройки',
                href: adminSettings(),
                icon: Settings,
            },
        );
    }

    return items;
});

const directoryNavItems = computed<NavItem[]>(() => {
    if (! isAdmin.value) {
        return [];
    }

    return [
        {
            title: 'Справочники',
            href: adminDirectories(),
            icon: BookMarked,
        },
    ];
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" label="Меню" />
            <NavMain
                v-if="directoryNavItems.length"
                :items="directoryNavItems"
                label="Справочники"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
