<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import UserController from '@/actions/App/Http/Controllers/Admin/UserController';
import UserFormFields from '@/components/admin/UserFormFields.vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { edit, index } from '@/routes/admin/users';
import type { UserRole } from '@/types/auth';

type RoleOption = {
    value: UserRole;
    label: string;
};

type RegionOption = {
    id: number;
    name: string;
};

type DistrictOption = {
    id: number;
    name: string;
    region_id: number;
};

type EditableUser = {
    id: number;
    name: string;
    iin: string | null;
    phone: string | null;
    email: string;
    role: UserRole;
    region_id: number | null;
    district_id: number | null;
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
    regions: RegionOption[];
    districts: DistrictOption[];
}>();
</script>

<template>
    <Head :title="`Изменить: ${user.name}`" />

    <div class="mx-auto flex h-full max-w-2xl flex-1 flex-col gap-4 p-4 lg:p-6">
        <Heading
            title="Изменить пользователя"
            :description="user.email"
        />

        <Form
            v-bind="UserController.update.form(props.user.id)"
            class="w-full max-w-xl space-y-5"
            v-slot="{ errors, processing }"
        >
            <UserFormFields
                mode="edit"
                :roles="roles"
                :regions="regions"
                :districts="districts"
                :errors="errors"
                :initial-name="user.name"
                :initial-iin="user.iin ?? ''"
                :initial-phone="user.phone ?? ''"
                :initial-role="user.role"
                :initial-region-id="user.region_id"
                :initial-district-id="user.district_id"
            />

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
