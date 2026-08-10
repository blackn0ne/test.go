<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit, index } from '@/routes/admin/users';
import type { UserRole } from '@/types/auth';

type RoleOption = {
    value: UserRole;
    label: string;
};

type EditableUser = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Юзеры',
                href: index(),
            },
            {
                title: 'Изменить',
                href: edit(0),
            },
        ],
    },
});

const props = defineProps<{
    user: EditableUser;
    roles: RoleOption[];
}>();
</script>

<template>
    <Head :title="`Изменить: ${user.name}`" />

    <div class="mx-auto flex h-full max-w-2xl flex-1 flex-col gap-4 p-4">
        <Heading
            title="Изменить пользователя"
            :description="user.email"
        />

        <Form
            v-bind="UserController.update.form(props.user.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="name">Имя</Label>
                <Input
                    id="name"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="email"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="role">Роль</Label>
                <select
                    id="role"
                    name="role"
                    required
                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                >
                    <option
                        v-for="role in roles"
                        :key="role.value"
                        :value="role.value"
                        :selected="role.value === user.role"
                    >
                        {{ role.label }}
                    </option>
                </select>
                <InputError :message="errors.role" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Новый пароль</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    placeholder="Оставьте пустым, чтобы не менять"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Подтверждение пароля</Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                />
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="processing">
                    Сохранить
                </Button>
                <Button as-child variant="outline">
                    <Link :href="index()">Отмена</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
