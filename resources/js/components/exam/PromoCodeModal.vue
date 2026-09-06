<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { watch } from 'vue';
import ExamAttemptController from '@/actions/App/Http/Controllers/ExamAttemptController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    InputOTP,
    InputOTPGroup,
    InputOTPSlot,
} from '@/components/ui/input-otp';

type Props = {
    examId: number;
};

const props = defineProps<Props>();
const open = defineModel<boolean>('open', { default: false });

const promoCode = defineModel<string>('promoCode', { default: '' });

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
    }
});
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Промокод</DialogTitle>
                <DialogDescription>
                    Введите 5-значный промокод для начала экзамена
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="ExamAttemptController.start.form(props.examId)"
                class="space-y-6"
                reset-on-error
                @error="promoCode = ''"
                #default="{ errors, processing }"
            >
                <input type="hidden" name="promo_code" :value="promoCode" />

                <div class="flex flex-col items-center gap-3">
                    <InputOTP
                        id="promo_code_otp"
                        v-model="promoCode"
                        :maxlength="5"
                        :disabled="processing"
                        autofocus
                    >
                        <InputOTPGroup>
                            <InputOTPSlot
                                v-for="index in 5"
                                :key="index"
                                :index="index - 1"
                                class="size-11 text-lg font-semibold uppercase"
                            />
                        </InputOTPGroup>
                    </InputOTP>
                    <InputError :message="errors.promo_code" />
                    <p class="text-center text-xs text-muted-foreground">
                        Цифры и заглавные латинские буквы
                    </p>
                </div>

                <DialogFooter class="sm:justify-center">
                    <Button
                        type="submit"
                        size="lg"
                        class="min-w-40"
                        :disabled="processing || promoCode.length !== 5"
                    >
                        Начать
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
