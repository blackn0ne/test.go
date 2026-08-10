<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import SettingsController from '@/actions/App/Http/Controllers/Admin/SettingsController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit as adminSettings } from '@/routes/admin/settings';

type SocialNetworks = {
    facebook: string;
    instagram: string;
    telegram: string;
    youtube: string;
    whatsapp: string;
};

type SiteSettings = {
    project_name: string | null;
    description: string | null;
    keywords: string | null;
    address: string | null;
    phone: string | null;
    social_networks: SocialNetworks;
    logo_url: string | null;
    favicon_url: string | null;
};

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Настройки',
                href: adminSettings(),
            },
        ],
    },
});

const props = defineProps<{
    settings: SiteSettings;
}>();

const socialFields: Array<{ key: keyof SocialNetworks; label: string }> = [
    { key: 'facebook', label: 'Facebook' },
    { key: 'instagram', label: 'Instagram' },
    { key: 'telegram', label: 'Telegram' },
    { key: 'youtube', label: 'YouTube' },
    { key: 'whatsapp', label: 'WhatsApp' },
];
</script>

<template>
    <Head title="Настройки" />

    <div class="mx-auto flex h-full max-w-3xl flex-1 flex-col gap-4 p-4">
        <Heading
            title="Настройки проекта"
            description="Название, описание, контакты и брендинг платформы"
        />

        <Form
            v-bind="SettingsController.update.form()"
            enctype="multipart/form-data"
            class="space-y-8"
            v-slot="{ errors, processing }"
        >
            <section class="space-y-4">
                <h3 class="text-base font-medium">Основное</h3>

                <div class="grid gap-2">
                    <Label for="project_name">Название проекта</Label>
                    <Input
                        id="project_name"
                        name="project_name"
                        :default-value="settings.project_name ?? ''"
                    />
                    <InputError :message="errors.project_name" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Описание</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        class="flex min-h-20 w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        :default-value="settings.description ?? ''"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="keywords">Ключевые слова</Label>
                    <Input
                        id="keywords"
                        name="keywords"
                        :default-value="settings.keywords ?? ''"
                        placeholder="тестирование, школа, экзамены"
                    />
                    <InputError :message="errors.keywords" />
                </div>
            </section>

            <section class="space-y-4">
                <h3 class="text-base font-medium">Брендинг</h3>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="logo">Логотип</Label>
                        <Input id="logo" type="file" name="logo" accept="image/*" />
                        <img
                            v-if="settings.logo_url"
                            :src="settings.logo_url"
                            alt="Logo"
                            class="mt-2 h-16 w-auto rounded border object-contain"
                        />
                        <InputError :message="errors.logo" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="favicon">Favicon</Label>
                        <Input
                            id="favicon"
                            type="file"
                            name="favicon"
                            accept="image/*"
                        />
                        <img
                            v-if="settings.favicon_url"
                            :src="settings.favicon_url"
                            alt="Favicon"
                            class="mt-2 h-10 w-10 rounded border object-contain"
                        />
                        <InputError :message="errors.favicon" />
                    </div>
                </div>
            </section>

            <section class="space-y-4">
                <h3 class="text-base font-medium">Контакты</h3>

                <div class="grid gap-2">
                    <Label for="address">Адрес</Label>
                    <Input
                        id="address"
                        name="address"
                        :default-value="settings.address ?? ''"
                    />
                    <InputError :message="errors.address" />
                </div>

                <div class="grid gap-2">
                    <Label for="phone">Телефон</Label>
                    <Input
                        id="phone"
                        name="phone"
                        :default-value="settings.phone ?? ''"
                    />
                    <InputError :message="errors.phone" />
                </div>
            </section>

            <section class="space-y-4">
                <h3 class="text-base font-medium">Соцсети</h3>

                <div
                    v-for="field in socialFields"
                    :key="field.key"
                    class="grid gap-2"
                >
                    <Label :for="`social_${field.key}`">{{ field.label }}</Label>
                    <Input
                        :id="`social_${field.key}`"
                        :name="`social_networks[${field.key}]`"
                        :default-value="settings.social_networks[field.key] ?? ''"
                        placeholder="https://"
                    />
                    <InputError
                        :message="errors[`social_networks.${field.key}`]"
                    />
                </div>
            </section>

            <Button type="submit" :disabled="processing">Сохранить</Button>
        </Form>
    </div>
</template>
