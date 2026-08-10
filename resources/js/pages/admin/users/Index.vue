<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import { create, edit, index } from '@/routes/admin/users';
import type { UserRole } from '@/types/auth';

type UserListItem = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    created_at: string;
};

type RoleOption = {
    value: UserRole;
    label: string;
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
    roles: RoleOption[];
}>();

const roleLabels: Record<UserRole, string> = {
    admin: 'Админ',
    school: 'Школа',
    user: 'Студент',
};
</script>

<template>
    <Head title="Юзеры" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between gap-4">
            <Heading
                title="Юзеры"
                description="Управление пользователями платформы"
            />
            <Button as-child>
                <Link :href="create()">Добавить</Link>
            </Button>
        </div>

        <Card>
            <CardHeader>
                <CardTitle>Список пользователей</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b text-left text-muted-foreground">
                                <th class="pb-3 pr-4 font-medium">Имя</th>
                                <th class="pb-3 pr-4 font-medium">Email</th>
                                <th class="pb-3 pr-4 font-medium">Роль</th>
                                <th class="pb-3 font-medium text-right">
                                    Действия
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="user in users"
                                :key="user.id"
                                class="border-b last:border-0"
                            >
                                <td class="py-3 pr-4">{{ user.name }}</td>
                                <td class="py-3 pr-4">{{ user.email }}</td>
                                <td class="py-3 pr-4">
                                    <Badge variant="secondary">
                                        {{ roleLabels[user.role] }}
                                    </Badge>
                                </td>
                                <td class="py-3 text-right">
                                    <div
                                        class="flex justify-end gap-2"
                                    >
                                        <Button
                                            as-child
                                            variant="outline"
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
                                                variant="destructive"
                                                size="sm"
                                            >
                                                Удалить
                                            </Button>
                                        </Form>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="users.length === 0">
                                <td
                                    colspan="4"
                                    class="py-8 text-center text-muted-foreground"
                                >
                                    Пользователи не найдены
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
