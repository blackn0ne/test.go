<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Clock, Play } from '@lucide/vue';
import { computed, ref } from 'vue';
import PromoCodeModal from '@/components/exam/PromoCodeModal.vue';
import { Badge } from '@/components/ui/badge';
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
    status_label?: string | null;
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
        :header-title="props.lobby.title"
    >
        <Head :title="props.lobby.title" />

        <div
            class="relative flex min-h-[calc(100dvh-4rem)] flex-1 flex-col overflow-hidden"
        >
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(0,0,0,0.04),transparent_55%),radial-gradient(circle_at_bottom_right,rgba(0,0,0,0.03),transparent_45%)] dark:bg-[radial-gradient(circle_at_top,rgba(255,255,255,0.06),transparent_55%)]"
            />
            <div
                class="pointer-events-none absolute top-1/4 left-1/2 size-[28rem] -translate-x-1/2 rounded-full bg-primary/5 blur-3xl"
            />

            <div
                class="relative z-10 flex flex-1 flex-col items-center justify-center px-6 py-16 text-center md:px-12"
            >
                <p
                    class="animate-in fade-in text-xs font-medium tracking-[0.24em] text-muted-foreground uppercase duration-700"
                >
                    Единое национальное тестирование
                </p>

                <h1
                    class="animate-in fade-in slide-in-from-bottom-4 mt-6 max-w-4xl text-4xl font-semibold tracking-tight duration-700 md:text-6xl md:leading-[1.05]"
                >
                    {{ props.lobby.title }}
                </h1>

                <p
                    class="animate-in fade-in slide-in-from-bottom-2 mt-5 text-lg text-muted-foreground duration-700 [animation-delay:120ms] md:text-xl"
                >
                    {{ props.lobby.period_label }}
                </p>

                <div
                    class="animate-in fade-in mt-6 flex flex-wrap items-center justify-center gap-2 duration-700 [animation-delay:220ms]"
                >
                    <Badge v-if="! canStart" variant="secondary">
                        {{ props.lobby.status_label ?? 'Ожидание' }}
                    </Badge>
                    <Badge v-else variant="default">Доступен</Badge>
                </div>

                <p
                    v-if="canStart && props.exam?.description"
                    class="animate-in fade-in mx-auto mt-6 max-w-2xl text-sm leading-6 text-muted-foreground duration-700 [animation-delay:320ms] md:text-base"
                >
                    {{ props.exam.description }}
                </p>
                <p
                    v-else-if="! canStart"
                    class="animate-in fade-in mx-auto mt-6 max-w-2xl text-sm leading-6 text-muted-foreground duration-700 [animation-delay:320ms] md:text-base"
                >
                    Экзамен создан, но ещё не открыт для студентов. Измените
                    статус на «Опубликован» в админке и убедитесь, что дата
                    начала уже наступила.
                </p>

                <div
                    class="animate-in fade-in zoom-in-95 mt-10 duration-700 [animation-delay:420ms]"
                >
                    <Button
                        v-if="canStart"
                        size="lg"
                        class="h-12 min-w-48 gap-2 rounded-full px-8 text-base"
                        data-test="exam-start-button"
                        @click="promoModalOpen = true"
                    >
                        <Play class="size-4" />
                        Начать
                    </Button>
                    <Button
                        v-else
                        size="lg"
                        variant="secondary"
                        class="h-12 min-w-48 gap-2 rounded-full px-8 text-base"
                        disabled
                    >
                        <Clock class="size-4" />
                        Ожидание
                    </Button>
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
