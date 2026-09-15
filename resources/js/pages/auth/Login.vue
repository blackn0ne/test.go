<script setup lang="ts">
import { Form, Head, usePage } from '@inertiajs/vue3';
import { Sparkles } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';

defineOptions({
    layout: null,
});

defineProps<{
    status?: string;
}>();

const page = usePage();
const appName = page.props.name as string;
const logoUrl = page.props.site.logo_url;
</script>

<template>
    <Head title="Кіру" />

    <div
        class="login-screen relative flex min-h-svh flex-col overflow-hidden bg-background"
    >
        <div class="pointer-events-none absolute inset-0 login-grid" />
        <div
            class="login-orb login-orb-a pointer-events-none absolute -top-24 -left-20 size-[22rem] rounded-full bg-sky-400/30 blur-3xl"
        />
        <div
            class="login-orb login-orb-b pointer-events-none absolute top-1/3 -right-24 size-[26rem] rounded-full bg-violet-400/25 blur-3xl"
        />
        <div
            class="login-orb login-orb-c pointer-events-none absolute -bottom-32 left-1/4 size-[30rem] rounded-full bg-emerald-400/20 blur-3xl"
        />
        <div
            class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_0%,hsl(var(--background)/0.35)_72%,hsl(var(--background)/0.55)_100%)]"
        />

        <div
            class="relative z-10 flex flex-1 flex-col items-center justify-center px-4 py-10 sm:px-6"
        >
            <div class="mb-8 text-center">
                <p
                    class="flex items-center justify-center gap-2 text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase"
                >
                    <Sparkles class="size-3.5 text-amber-500" />
                    ҰЛТТЫҚ БЫРЫҢҒАЙ ТЕСТІЛЕУ
                    <Sparkles class="size-3.5 text-amber-500" />
                </p>
                <div class="mt-6 flex flex-col items-center gap-3">
                    <div
                        class="flex size-16 items-center justify-center overflow-hidden rounded-2xl border border-border/60 bg-background shadow-lg"
                    >
                        <img
                            v-if="logoUrl"
                            :src="logoUrl"
                            :alt="appName"
                            class="size-full object-contain p-2"
                        />
                        <div
                            v-else
                            class="flex size-full items-center justify-center bg-gradient-to-br from-sky-500 to-violet-500"
                        >
                            <AppLogoIcon class="size-8 fill-white text-white" />
                        </div>
                    </div>
                    <h1
                        class="bg-gradient-to-br from-foreground via-foreground to-foreground/70 bg-clip-text text-3xl font-bold tracking-tight sm:text-4xl"
                    >
                        {{ appName }}
                    </h1>
                    <p class="max-w-sm text-sm text-muted-foreground">
                        Жүйеге кіру үшін ЖСН және парольді енгізіңіз
                    </p>
                </div>
            </div>

            <div
                class="w-full max-w-md overflow-hidden rounded-2xl border border-border/60 bg-background/85 p-6 shadow-xl backdrop-blur-md sm:p-8"
            >
                <div
                    v-if="status"
                    class="mb-4 rounded-xl border border-emerald-500/30 bg-emerald-500/10 px-3 py-2 text-center text-sm font-medium text-emerald-700 dark:text-emerald-300"
                >
                    {{ status }}
                </div>

                <Form
                    v-bind="store.form()"
                    :reset-on-success="['password']"
                    v-slot="{ errors, processing }"
                    class="flex flex-col gap-5"
                >
                    <div class="grid gap-4">
                        <div class="grid gap-1.5">
                            <Input
                                id="iin"
                                name="iin"
                                required
                                autofocus
                                inputmode="numeric"
                                maxlength="12"
                                autocomplete="username"
                                placeholder="ЖСН"
                                class="h-11 rounded-xl"
                            />
                            <InputError :message="errors.iin" />
                        </div>

                        <div class="grid gap-1.5">
                            <PasswordInput
                                id="password"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Пароль"
                                class="h-11 rounded-xl"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <Label
                            for="remember"
                            class="flex items-center gap-3 rounded-xl border border-border/60 bg-muted/20 px-3 py-2.5"
                        >
                            <Checkbox id="remember" name="remember" />
                            <span class="text-sm">Мені есте сақтау</span>
                        </Label>

                        <Button
                            type="submit"
                            class="h-11 w-full rounded-xl text-base font-semibold shadow-sm"
                            :disabled="processing"
                        >
                            <Spinner v-if="processing" />
                            Кіру
                        </Button>
                    </div>
                </Form>
            </div>
        </div>
    </div>
</template>

<style scoped>
.login-grid {
    background-image:
        linear-gradient(to right, rgb(148 163 184 / 0.08) 1px, transparent 1px),
        linear-gradient(to bottom, rgb(148 163 184 / 0.08) 1px, transparent 1px);
    background-size: 48px 48px;
    animation: login-grid-drift 24s linear infinite;
}

.login-orb {
    animation: login-orb-float 14s ease-in-out infinite;
}

.login-orb-b {
    animation-delay: -4s;
    animation-duration: 18s;
}

.login-orb-c {
    animation-delay: -8s;
    animation-duration: 20s;
}

@keyframes login-orb-float {
    0%,
    100% {
        transform: translate(0, 0) scale(1);
    }

    33% {
        transform: translate(28px, -18px) scale(1.06);
    }

    66% {
        transform: translate(-18px, 22px) scale(0.94);
    }
}

@keyframes login-grid-drift {
    from {
        transform: translateY(0);
    }

    to {
        transform: translateY(48px);
    }
}
</style>
