<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import UserFormFields from '@/components/admin/UserFormFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { create, index } from '@/routes/admin/users';
import type { UserRole } from '@/types/auth';

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
            {
                title: 'Добавить',
                href: create(),
            },
        ],
    },
});

defineProps<{
    roles: RoleOption[];
}>();
</script>

<template>
    <Head title="Добавить пользователя" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4 lg:p-6">
        <Heading
            title="Добавить пользователя"
            description="ФИО, ИИН и телефон. Email создаётся автоматически."
        />

        <Form
            v-bind="UserController.store.form()"
            class="w-full max-w-lg space-y-5"
            v-slot="{ errors, processing }"
        >
            <UserFormFields mode="create" :roles="roles" :errors="errors" />

            <div class="flex flex-wrap gap-2 pt-1">
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
