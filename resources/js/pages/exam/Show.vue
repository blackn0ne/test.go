<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Clock, Play, Sparkles } from '@lucide/vue';
import { computed, ref } from 'vue';
import PromoCodeModal from '@/components/exam/PromoCodeModal.vue';
import { Button } from '@/components/ui/button';
import ExamScreenLayout from '@/layouts/exam/ExamScreenLayout.vue';
import type { ExamInfo, ExamSection } from '@/types/exam';

defineOptions({
    layout: null,
});

type LobbyInfo = {
    title: string;
    period_label: string;
    available: boolean;
};

const props = defineProps<{
    exam: ExamInfo | null;
    sections: ExamSection[];
    requiresPromoCode: boolean;
    lobby: LobbyInfo;
}>();

const promoModalOpen = ref(false);
const promoCode = ref('');

const canStart = computed(() => props.lobby.available && props.exam !== null);
</script>

<template>
    <ExamScreenLayout
        :sections="props.sections"
        header-title="ЕНТ"
    >
        <Head title="ЕНТ" />

        <div class="flex flex-1 flex-col p-4 lg:p-8">
            <div class="flex flex-1 items-center justify-center">
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
                                {{ props.lobby.title }}
                            </h2>
                            <p
                                class="animate-in fade-in slide-in-from-bottom-2 text-lg text-muted-foreground duration-700 [animation-delay:150ms]"
                            >
                                {{ props.lobby.period_label }}
                            </p>
                            <p
                                v-if="canStart && props.exam?.description"
                                class="animate-in fade-in mx-auto max-w-lg text-sm text-muted-foreground duration-700 [animation-delay:300ms]"
                            >
                                {{ props.exam.description }}
                            </p>
                            <p
                                v-else-if="! canStart"
                                class="animate-in fade-in mx-auto max-w-lg text-sm text-muted-foreground duration-700 [animation-delay:300ms]"
                            >
                                Экзамен для вашего направления ещё не
                                опубликован. Когда администратор откроет доступ,
                                кнопка «Начать» станет активной.
                            </p>
                        </div>

                        <Button
                            v-if="canStart"
                            size="lg"
                            class="animate-in fade-in zoom-in-95 min-w-44 gap-2 duration-700 [animation-delay:450ms]"
                            data-test="exam-start-button"
                            @click="promoModalOpen = true"
                        >
                            <Play class="size-4" />
                            Начать
                        </Button>
                        <Button
                            v-else
                            size="lg"
                            class="animate-in fade-in zoom-in-95 min-w-44 gap-2 duration-700 [animation-delay:450ms]"
                            disabled
                        >
                            <Clock class="size-4" />
                            Ожидание
                        </Button>
                    </div>
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
