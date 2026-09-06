<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Play, Sparkles } from '@lucide/vue';
import { ref } from 'vue';
import PromoCodeModal from '@/components/exam/PromoCodeModal.vue';
import { Button } from '@/components/ui/button';
import ExamScreenLayout from '@/layouts/exam/ExamScreenLayout.vue';
import type { ExamInfo, ExamSection } from '@/types/exam';

defineOptions({
    layout: null,
});

const props = defineProps<{
    exam: ExamInfo | null;
    sections: ExamSection[];
    requiresPromoCode: boolean;
}>();

const promoModalOpen = ref(false);
const promoCode = ref('');
</script>

<template>
    <ExamScreenLayout
        :sections="props.sections"
        :header-title="props.exam?.title ?? 'ЕНТ'"
    >
        <Head :title="props.exam?.title ?? 'ЕНТ'" />

        <div class="flex flex-1 flex-col p-4 lg:p-8">
            <div
                v-if="props.exam"
                class="flex flex-1 items-center justify-center"
            >
                <div
                    class="relative w-full max-w-2xl overflow-hidden rounded-3xl border bg-card p-8 shadow-sm md:p-12"
                >
                    <div
                        class="pointer-events-none absolute inset-0 opacity-60"
                    >
                        <div
                            class="absolute -top-24 -right-24 size-64 animate-pulse rounded-full bg-primary/10 blur-3xl"
                        />
                        <div
                            class="absolute -bottom-24 -left-24 size-64 animate-pulse rounded-full bg-primary/5 blur-3xl [animation-delay:700ms]"
                        />
                    </div>

                    <div
                        class="relative flex flex-col items-center gap-6 text-center"
                    >
                        <div
                            class="inline-flex items-center gap-2 rounded-full border bg-background/80 px-4 py-1.5 text-xs font-medium text-muted-foreground backdrop-blur"
                        >
                            <Sparkles class="size-3.5 text-primary" />
                            <span>Единое национальное тестирование</span>
                        </div>

                        <div class="space-y-3">
                            <h2
                                class="animate-in fade-in slide-in-from-bottom-4 text-3xl font-bold tracking-tight duration-700 md:text-4xl"
                            >
                                {{ props.exam.title }}
                            </h2>
                            <p
                                v-if="props.exam.period_label"
                                class="animate-in fade-in slide-in-from-bottom-2 text-lg text-muted-foreground duration-700 [animation-delay:150ms]"
                            >
                                {{ props.exam.period_label }}
                            </p>
                            <p
                                v-if="props.exam.description"
                                class="animate-in fade-in mx-auto max-w-lg text-sm text-muted-foreground duration-700 [animation-delay:300ms]"
                            >
                                {{ props.exam.description }}
                            </p>
                        </div>

                        <Button
                            size="lg"
                            class="animate-in fade-in zoom-in-95 min-w-44 gap-2 duration-700 [animation-delay:450ms]"
                            data-test="exam-start-button"
                            @click="promoModalOpen = true"
                        >
                            <Play class="size-4" />
                            Начать
                        </Button>
                    </div>
                </div>
            </div>

            <div
                v-else
                class="flex flex-1 items-center justify-center"
            >
                <div
                    class="max-w-md rounded-2xl border bg-card p-8 text-center shadow-sm"
                >
                    <h2 class="text-lg font-semibold">
                        Экзамен пока недоступен
                    </h2>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Когда администратор опубликует экзамен для вашего
                        направления, он появится здесь.
                    </p>
                </div>
            </div>
        </div>

        <PromoCodeModal
            v-if="props.exam && props.requiresPromoCode"
            v-model:open="promoModalOpen"
            v-model:promo-code="promoCode"
            :exam-id="props.exam.id"
        />
    </ExamScreenLayout>
</template>
