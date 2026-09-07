<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { AlertCircle, Sparkles, Ticket } from '@lucide/vue';
import { ref, watch } from 'vue';
import ExamAttemptController from '@/actions/App/Http/Controllers/ExamAttemptController';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';
import { cn } from '@/lib/utils';

type Props = {
    examId: number;
};

type FormErrors = Record<string, string | string[] | undefined>;

const props = defineProps<Props>();
const open = defineModel<boolean>('open', { default: false });

const promoCode = defineModel<string>('promoCode', { default: '' });

const hasError = ref(false);

watch(promoCode, (value) => {
    const sanitized = value
        .toUpperCase()
        .replace(/[^0-9A-Z]/g, '')
        .slice(0, 5);

    if (sanitized !== value) {
        promoCode.value = sanitized;
    }
});

watch(open, (isOpen) => {
    if (! isOpen) {
        promoCode.value = '';
        hasError.value = false;
    }
});

function transformPromoCode(): { promo_code: string } {
    return {
        promo_code: promoCode.value,
    };
}

function firstError(error: string | string[] | undefined): string | undefined {
    if (! error) {
        return undefined;
    }

    return Array.isArray(error) ? error[0] : error;
}

function resolveFormError(errors: FormErrors): string | undefined {
    for (const key of [
        'promo_code',
        'exam',
        'questions',
        'blueprint',
        'direction',
    ]) {
        const message = firstError(errors[key]);

        if (message) {
            return message;
        }
    }

    for (const message of Object.values(errors)) {
        const resolved = firstError(message);

        if (resolved) {
            return resolved;
        }
    }

    return undefined;
}

function resolveErrorTitle(message: string | undefined): string {
    if (! message) {
        return 'Ошибка';
    }

    if (message.includes('использован')) {
        return 'Промокод уже использован';
    }

    if (
        message.includes('истёк')
        || message.includes('не подходит')
        || message.includes('не действителен')
        || message.includes('действителен для')
        || message.includes('проводится в')
        || message.includes('Сейчас')
    ) {
        return 'Промокод недействителен';
    }

    if (
        message.includes('не найден')
        || message.includes('не существует')
    ) {
        return 'Промокод не найден';
    }

    if (message.includes('школ')) {
        return 'Промокод не для вашей школы';
    }

    if (message.includes('завершили')) {
        return 'Экзамен уже завершён';
    }

    if (message.includes('недоступен')) {
        return 'Экзамен недоступен';
    }

    if (message.includes('Введите промокод')) {
        return 'Введите промокод';
    }

    if (message.includes('Недостаточно вопросов')) {
        return 'Не хватает вопросов';
    }

    if (message.includes('шаблон') || message.includes('направление')) {
        return 'Экзамен не настроен';
    }

    return 'Не удалось начать экзамен';
}

function handleFormError(): void {
    hasError.value = true;
}
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent
            class="gap-0 overflow-hidden border-border/60 p-0 sm:max-w-md"
        >
            <div
                class="border-b border-border/50 bg-gradient-to-br from-sky-500/10 via-background to-violet-500/10 px-6 pt-6 pb-5"
            >
                <DialogHeader class="items-center space-y-3 text-center">
                    <div
                        class="flex size-14 items-center justify-center rounded-2xl bg-primary/10 text-primary shadow-sm"
                    >
                        <Ticket class="size-7" />
                    </div>
                    <DialogTitle class="text-xl font-semibold">
                        Промокод
                    </DialogTitle>
                    <DialogDescription class="max-w-xs text-sm">
                        Введите 5-символьный код для начала экзамена
                    </DialogDescription>
                </DialogHeader>
            </div>

            <Form
                v-bind="ExamAttemptController.start.form(props.examId)"
                :transform="transformPromoCode"
                class="space-y-5 px-6 py-6"
                preserve-scroll
                preserve-state
                #default="{ errors, processing }"
                @error="handleFormError"
            >
                <div
                    class="flex flex-col items-center gap-4"
                    :class="cn(hasError && 'animate-shake')"
                >
                    <InputOTP
                        id="promo_code_otp"
                        v-model="promoCode"
                        :maxlength="5"
                        :disabled="processing"
                        :aria-invalid="hasError || undefined"
                        autofocus
                    >
                        <InputOTPGroup
                            :class="
                                cn(
                                    'gap-2',
                                    hasError && '[&_[data-slot=input-otp-slot]]:border-destructive/60',
                                )
                            "
                        >
                            <InputOTPSlot
                                v-for="index in 5"
                                :key="index"
                                :index="index - 1"
                                class="size-12 rounded-xl border-2 text-lg font-bold uppercase first:rounded-xl last:rounded-xl"
                            />
                        </InputOTPGroup>
                    </InputOTP>

                    <p class="text-center text-xs text-muted-foreground">
                        Цифры и заглавные латинские буквы A–Z
                    </p>
                </div>

                <div
                    v-if="resolveFormError(errors)"
                    class="flex items-start gap-3 rounded-2xl border border-destructive/25 bg-destructive/5 px-4 py-3"
                    role="alert"
                >
                    <AlertCircle
                        class="mt-0.5 size-5 shrink-0 text-destructive"
                    />
                    <div class="space-y-1 text-sm">
                        <p class="font-semibold text-destructive">
                            {{
                                resolveErrorTitle(
                                    resolveFormError(errors),
                                )
                            }}
                        </p>
                        <p class="text-destructive/80">
                            {{ resolveFormError(errors) }}
                        </p>
                    </div>
                </div>

                <Button
                    type="submit"
                    size="lg"
                    class="h-12 w-full gap-2 rounded-full text-base shadow-md shadow-primary/15"
                    :disabled="processing || promoCode.length !== 5"
                >
                    <Sparkles class="size-4" />
                    Начать экзамен
                </Button>
            </Form>
        </DialogContent>
    </Dialog>
</template>

<style scoped>
@keyframes shake {
    0%,
    100% {
        transform: translateX(0);
    }

    20%,
    60% {
        transform: translateX(-6px);
    }

    40%,
    80% {
        transform: translateX(6px);
    }
}

.animate-shake {
    animation: shake 0.45s ease-in-out;
}
</style>
