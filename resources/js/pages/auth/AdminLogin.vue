<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import AdminAuthenticatedSessionController from '@/actions/App/Http/Controllers/Auth/AdminAuthenticatedSessionController';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store as loginStore } from '@/routes/login';

defineOptions({
    layout: {
        title: 'Вход администратора',
        description: 'Email и пароль для админ-панели',
    },
});

defineProps<{
    status?: string;
}>();
</script>

<template>
    <Head title="Админ вход" />

    <div
        v-if="status"
        class="mb-4 text-center text-sm font-medium text-green-600"
    >
        {{ status }}
    </div>

    <Form
        v-bind="AdminAuthenticatedSessionController.store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-5"
    >
        <div class="grid gap-4">
            <div class="grid gap-1.5">
                <Label for="email">Email</Label>
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    autocomplete="email"
                    placeholder="admin@example.com"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-1.5">
                <Label for="password">Пароль</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    required
                    autocomplete="current-password"
                    placeholder="Пароль"
                />
                <InputError :message="errors.password" />
            </div>

            <Label for="remember" class="flex items-center gap-3">
                <Checkbox id="remember" name="remember" />
                <span class="text-sm">Запомнить меня</span>
            </Label>

            <Button type="submit" class="w-full" :disabled="processing">
                <Spinner v-if="processing" />
                Войти
            </Button>
        </div>

        <p class="text-center text-sm text-muted-foreground">
            Студент или школа?
            <TextLink :href="loginStore.url()">Вход по ИИН</TextLink>
        </p>
    </Form>
</template>
