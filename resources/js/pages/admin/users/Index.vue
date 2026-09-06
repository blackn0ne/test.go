<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { create, edit, index } from '@/routes/admin/users';
import type { UserRole } from '@/types/auth';

type UserListItem = {
    id: number;
    name: string;
    iin: string | null;
    phone: string | null;
    email: string;
    role: UserRole;
    created_at: string;
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Юзеры',
                href: index(),
            },
        ],
    },
});

defineProps<{
    users: UserListItem[];
}>();

const roleLabels: Record<UserRole, string> = {
    admin: 'Админ',
    school: 'Школа',
    user: 'Студент',
};
</script>

<template>
    <Head title="Юзеры" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 lg:p-6">
        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Юзеры"
                description="Управление пользователями платформы"
            />
            <Button as-child size="sm">
                <Link :href="create()">Добавить</Link>
            </Button>
        </div>

        <section class="overflow-hidden rounded-lg border bg-card">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr
                            class="border-b bg-muted/10 text-left text-xs text-muted-foreground"
                        >
                            <th class="px-4 py-2.5 font-medium">ФИО</th>
                            <th class="px-4 py-2.5 font-medium">ИИН</th>
                            <th class="hidden px-4 py-2.5 font-medium md:table-cell">
                                Телефон
                            </th>
                            <th class="hidden px-4 py-2.5 font-medium lg:table-cell">
                                Email
                            </th>
                            <th class="px-4 py-2.5 font-medium">Роль</th>
                            <th class="px-4 py-2.5 text-right font-medium">
                                Действия
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="user in users"
                            :key="user.id"
                            class="border-b last:border-0 hover:bg-muted/20"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ user.name }}
                            </td>
                            <td class="px-4 py-3 tabular-nums text-muted-foreground">
                                {{ user.iin ?? '—' }}
                            </td>
                            <td
                                class="hidden px-4 py-3 tabular-nums md:table-cell"
                            >
                                {{ user.phone ?? '—' }}
                            </td>
                            <td
                                class="hidden px-4 py-3 text-muted-foreground lg:table-cell"
                            >
                                {{ user.email }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    variant="outline"
                                    class="rounded-sm px-1.5 py-0 text-[11px] font-normal"
                                >
                                    {{ roleLabels[user.role] }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        as-child
                                        variant="ghost"
                                        size="sm"
                                    >
                                        <Link :href="edit(user.id)">
                                            Изменить
                                        </Link>
                                    </Button>
                                    <Form
                                        v-bind="
                                            UserController.destroy.form(
                                                user.id,
                                            )
                                        "
                                        class="inline"
                                    >
                                        <Button
                                            type="submit"
                                            variant="ghost"
                                            size="sm"
                                            class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                        >
                                            Удалить
                                        </Button>
                                    </Form>
                                </div>
                            </td>
                        </tr>
                        <tr v-if="users.length === 0">
                            <td
                                colspan="6"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                Пользователи не найдены
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
