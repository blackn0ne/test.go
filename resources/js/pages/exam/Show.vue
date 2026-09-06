<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Clock, Play, Sparkles } from '@lucide/vue';
import { computed, ref } from 'vue';
import PromoCodeModal from '@/components/exam/PromoCodeModal.vue';
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

const headerTitle = computed(
    () =>
        `${props.lobby.title.toLocaleLowerCase('kk-KZ')} — ${props.lobby.period_label}`,
);
</script>

<template>
    <ExamScreenLayout
        :sections="props.sections"
        :header-title="headerTitle"
        lobby-mode
    >
        <Head :title="props.lobby.title" />

        <div
            class="exam-lobby relative flex min-h-[calc(100dvh-4rem)] flex-1 flex-col overflow-hidden bg-transparent"
        >
            <!-- Animated mesh background -->
            <div class="pointer-events-none absolute inset-0 exam-lobby-grid" />
            <div
                class="exam-lobby-orb exam-lobby-orb-a pointer-events-none absolute -top-24 -left-20 size-[22rem] rounded-full bg-sky-400/30 blur-3xl"
            />
            <div
                class="exam-lobby-orb exam-lobby-orb-b pointer-events-none absolute top-1/3 -right-24 size-[26rem] rounded-full bg-violet-400/25 blur-3xl"
            />
            <div
                class="exam-lobby-orb exam-lobby-orb-c pointer-events-none absolute -bottom-32 left-1/4 size-[30rem] rounded-full bg-emerald-400/20 blur-3xl"
            />
            <div
                class="exam-lobby-orb exam-lobby-orb-d pointer-events-none absolute top-1/2 left-1/2 size-[18rem] -translate-x-1/2 -translate-y-1/2 rounded-full bg-amber-300/20 blur-3xl"
            />
            <div
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center,transparent_0%,hsl(var(--background)/0.35)_72%,hsl(var(--background)/0.55)_100%)]"
            />

            <div
                class="relative z-10 flex flex-1 flex-col items-center justify-center px-6 py-16 text-center md:px-12"
            >
                <p
                    class="animate-in fade-in flex items-center justify-center gap-2 text-xs font-medium tracking-[0.22em] text-muted-foreground uppercase duration-700"
                >
                    <Sparkles class="size-3.5 text-amber-500" />
                    ҰЛТТЫҚ БЫРЫҢҒАЙ ТЕСТІЛЕУ
                    <Sparkles class="size-3.5 text-amber-500" />
                </p>

                <h1
                    class="animate-in fade-in slide-in-from-bottom-4 mt-6 max-w-4xl bg-gradient-to-br from-foreground via-foreground to-foreground/70 bg-clip-text text-4xl font-bold tracking-tight duration-700 md:text-6xl md:leading-[1.05]"
                >
                    {{ props.lobby.title }}
                </h1>

                <p
                    class="animate-in fade-in slide-in-from-bottom-2 mt-5 text-lg font-medium text-muted-foreground duration-700 [animation-delay:120ms] md:text-xl"
                >
                    {{ props.lobby.period_label }}
                </p>

                <p
                    v-if="canStart && props.exam?.description"
                    class="animate-in fade-in mx-auto mt-6 max-w-2xl text-sm leading-6 text-muted-foreground duration-700 [animation-delay:280ms] md:text-base"
                >
                    {{ props.exam.description }}
                </p>
                <p
                    v-else-if="! canStart"
                    class="animate-in fade-in mx-auto mt-6 max-w-2xl text-sm leading-6 text-muted-foreground duration-700 [animation-delay:280ms] md:text-base"
                >
                    Экзамен создан, но ещё не открыт для студентов. Измените
                    статус на «Опубликован» в админке и убедитесь, что дата
                    начала уже наступила.
                </p>

                <div
                    class="animate-in fade-in zoom-in-95 mt-12 duration-700 [animation-delay:420ms]"
                >
                    <button
                        v-if="canStart"
                        type="button"
                        class="exam-start-button group relative"
                        data-test="exam-start-button"
                        @click="promoModalOpen = true"
                    >
                        <span class="exam-start-button-glow" />
                        <span class="exam-start-button-ring" />
                        <span class="exam-start-button-inner">
                            <span class="exam-start-button-icon">
                                <Play
                                    class="size-5 fill-current pl-0.5"
                                    stroke-width="0"
                                />
                            </span>
                            <span class="flex flex-col items-start text-left">
                                <span
                                    class="text-[10px] font-medium tracking-[0.18em] text-white/70 uppercase"
                                >
                                    Бастау
                                </span>
                                <span class="text-lg font-bold text-white">
                                    Начать
                                </span>
                            </span>
                        </span>
                    </button>

                    <button
                        v-else
                        type="button"
                        class="exam-wait-button"
                        disabled
                    >
                        <span class="exam-wait-button-inner">
                            <Clock class="size-5 animate-pulse" />
                            <span>Ожидание</span>
                        </span>
                    </button>
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

<style scoped>
.exam-lobby-grid {
    background-image:
        linear-gradient(to right, hsl(var(--border) / 0.35) 1px, transparent 1px),
        linear-gradient(to bottom, hsl(var(--border) / 0.35) 1px, transparent 1px);
    background-size: 48px 48px;
    mask-image: radial-gradient(circle at center, black 20%, transparent 78%);
    animation: exam-grid-drift 24s linear infinite;
}

.exam-lobby-orb {
    animation: exam-orb-float 14s ease-in-out infinite;
}

.exam-lobby-orb-b {
    animation-delay: -4s;
    animation-duration: 18s;
}

.exam-lobby-orb-c {
    animation-delay: -8s;
    animation-duration: 20s;
}

.exam-lobby-orb-d {
    animation-delay: -2s;
    animation-duration: 16s;
}

.exam-start-button {
    position: relative;
    display: inline-flex;
    border: none;
    background: transparent;
    padding: 3px;
    cursor: pointer;
    border-radius: 9999px;
    transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.exam-start-button:hover {
    transform: scale(1.04) translateY(-2px);
}

.exam-start-button:active {
    transform: scale(0.98);
}

.exam-start-button-glow {
    position: absolute;
    inset: -8px;
    border-radius: 9999px;
    background: linear-gradient(
        120deg,
        #38bdf8,
        #8b5cf6,
        #10b981,
        #f59e0b,
        #38bdf8
    );
    background-size: 300% 300%;
    filter: blur(16px);
    opacity: 0.55;
    animation: exam-gradient-shift 6s ease infinite;
    transition: opacity 0.3s ease;
}

.exam-start-button:hover .exam-start-button-glow {
    opacity: 0.85;
}

.exam-start-button-ring {
    position: absolute;
    inset: 0;
    border-radius: 9999px;
    background: conic-gradient(
        from var(--exam-ring-angle, 0deg),
        #38bdf8,
        #8b5cf6,
        #10b981,
        #f59e0b,
        #ef4444,
        #38bdf8
    );
    animation: exam-ring-spin 4s linear infinite;
}

.exam-start-button-inner {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 1rem;
    border-radius: 9999px;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #0f172a 100%);
    padding: 0.85rem 1.75rem 0.85rem 0.85rem;
    box-shadow:
        inset 0 1px 0 rgb(255 255 255 / 0.12),
        0 12px 40px rgb(15 23 42 / 0.35);
    overflow: hidden;
}

.exam-start-button-inner::before {
    content: '';
    position: absolute;
    inset: 0;
    background: linear-gradient(
        105deg,
        transparent 40%,
        rgb(255 255 255 / 0.14) 50%,
        transparent 60%
    );
    transform: translateX(-120%);
    animation: exam-shimmer 3.5s ease-in-out infinite;
}

.exam-start-button-icon {
    display: flex;
    width: 3rem;
    height: 3rem;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    background: linear-gradient(135deg, #38bdf8, #8b5cf6);
    color: white;
    box-shadow: 0 8px 24px rgb(56 189 248 / 0.45);
    transition: transform 0.35s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.exam-start-button:hover .exam-start-button-icon {
    transform: scale(1.08) rotate(-6deg);
}

.exam-wait-button {
    border: none;
    border-radius: 9999px;
    padding: 3px;
    background: hsl(var(--muted));
    cursor: not-allowed;
    opacity: 0.75;
}

.exam-wait-button-inner {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    border-radius: 9999px;
    background: hsl(var(--background));
    padding: 0.9rem 1.75rem;
    font-weight: 600;
    color: hsl(var(--muted-foreground));
}

@property --exam-ring-angle {
    syntax: '<angle>';
    initial-value: 0deg;
    inherits: false;
}

@keyframes exam-ring-spin {
    to {
        --exam-ring-angle: 360deg;
    }
}

@keyframes exam-gradient-shift {
    0%,
    100% {
        background-position: 0% 50%;
    }

    50% {
        background-position: 100% 50%;
    }
}

@keyframes exam-shimmer {
    0%,
    70%,
    100% {
        transform: translateX(-120%);
    }

    85% {
        transform: translateX(120%);
    }
}

@keyframes exam-orb-float {
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

@keyframes exam-grid-drift {
    from {
        transform: translateY(0);
    }

    to {
        transform: translateY(48px);
    }
}
</style>
