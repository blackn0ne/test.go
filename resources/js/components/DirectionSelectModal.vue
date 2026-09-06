<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { Check } from '@lucide/vue';
import { computed, watch } from 'vue';
import UserDirectionController from '@/actions/App/Http/Controllers/UserDirectionController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';
import type { DirectionOption } from '@/types';

type Props = {
    required?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    required: false,
});

const open = defineModel<boolean>('open', { default: false });

const page = usePage();

const directions = computed(
    () => (page.props.directions as DirectionOption[]) ?? [],
);

const selectedDirectionId = computed(() => {
    const user = page.props.auth.user;

    return user?.direction?.id ?? null;
});

const form = useForm({
    direction_id: selectedDirectionId.value as number | null,
});

watch(selectedDirectionId, (directionId) => {
    if (directionId !== null) {
        form.direction_id = directionId;
    }
});

watch(open, (isOpen) => {
    if (isOpen && selectedDirectionId.value !== null) {
        form.direction_id = selectedDirectionId.value;
    }
});

const selectedDirection = computed(() =>
    directions.value.find((direction) => direction.id === form.direction_id),
);

function selectDirection(directionId: number) {
    form.direction_id = directionId;
}

const submit = () => {
    form.put(UserDirectionController.update.url(), {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
    });
};

const handleOpenChange = (value: boolean) => {
    if (props.required && ! value) {
        return;
    }

    open.value = value;
};
</script>

<template>
    <Dialog :open="open" @update:open="handleOpenChange">
        <DialogContent
            class="gap-0 overflow-hidden p-0 sm:max-w-4xl"
            :show-close-button="! required"
            @pointer-down-outside="required ? $event.preventDefault() : undefined"
            @escape-key-down="required ? $event.preventDefault() : undefined"
        >
            <DialogHeader class="space-y-2 border-b px-6 py-5">
                <DialogTitle class="text-xl">
                    {{
                        required
                            ? 'Выберите направление'
                            : 'Сменить направление'
                    }}
                </DialogTitle>
                <DialogDescription class="text-sm leading-relaxed">
                    {{
                        required
                            ? 'Для работы с платформой укажите профильное направление ЕНТ.'
                            : 'Выберите новое направление. Оно будет использоваться при генерации экзаменов.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form @submit.prevent="submit">
                <div class="max-h-[min(60vh,32rem)] overflow-y-auto px-6 py-5">
                    <div
                        v-if="directions.length === 0"
                        class="rounded-xl border border-dashed bg-muted/20 px-4 py-10 text-center text-sm text-muted-foreground"
                    >
                        Направления пока не добавлены. Обратитесь к
                        администратору.
                    </div>

                    <div
                        v-else
                        class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                        role="radiogroup"
                        aria-label="Направление"
                    >
                        <button
                            v-for="direction in directions"
                            :key="direction.id"
                            type="button"
                            role="radio"
                            :aria-checked="form.direction_id === direction.id"
                            class="group relative flex h-full flex-col rounded-xl border bg-card p-4 text-left transition-all duration-200 hover:border-primary/40 hover:shadow-sm focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2"
                            :class="
                                cn(
                                    form.direction_id === direction.id
                                        ? 'border-primary bg-primary/5 shadow-sm ring-1 ring-primary/20'
                                        : 'border-border/70',
                                )
                            "
                            @click="selectDirection(direction.id)"
                        >
                            <div class="mb-3 flex items-start justify-between gap-2">
                                <Badge
                                    variant="secondary"
                                    class="font-mono text-xs tracking-wide"
                                >
                                    {{ direction.code }}
                                </Badge>
                                <span
                                    class="flex size-5 shrink-0 items-center justify-center rounded-full border transition-colors"
                                    :class="
                                        form.direction_id === direction.id
                                            ? 'border-primary bg-primary text-primary-foreground'
                                            : 'border-muted-foreground/30 bg-background text-transparent group-hover:border-primary/40'
                                    "
                                >
                                    <Check class="size-3" />
                                </span>
                            </div>

                            <span class="mb-2 line-clamp-2 text-sm font-semibold leading-snug">
                                {{ direction.name }}
                            </span>

                            <div class="mt-auto space-y-1.5 pt-2">
                                <p
                                    v-for="subject in direction.subjects"
                                    :key="subject.id"
                                    class="flex items-center gap-2 text-xs text-muted-foreground"
                                >
                                    <span
                                        class="size-1 shrink-0 rounded-full bg-primary/60"
                                    />
                                    <span class="line-clamp-1">
                                        {{ subject.name }}
                                    </span>
                                </p>
                            </div>
                        </button>
                    </div>

                    <InputError
                        :message="form.errors.direction_id"
                        class="mt-4"
                    />
                </div>

                <DialogFooter
                    class="flex-col gap-3 border-t bg-muted/20 px-6 py-4 sm:flex-row sm:items-center sm:justify-between"
                >
                    <p
                        v-if="selectedDirection"
                        class="text-sm text-muted-foreground"
                    >
                        Выбрано:
                        <span class="font-medium text-foreground">
                            {{ selectedDirection.code }} —
                            {{ selectedDirection.name }}
                        </span>
                    </p>
                    <p v-else class="text-sm text-muted-foreground">
                        Выберите направление из списка
                    </p>

                    <div class="flex w-full shrink-0 gap-2 sm:w-auto">
                        <Button
                            v-if="! required"
                            type="button"
                            variant="outline"
                            class="flex-1 sm:flex-none"
                            @click="open = false"
                        >
                            Отмена
                        </Button>
                        <Button
                            type="submit"
                            class="flex-1 sm:flex-none"
                            :disabled="form.processing || ! form.direction_id"
                        >
                            <Spinner v-if="form.processing" />
                            Сохранить
                        </Button>
                    </div>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
