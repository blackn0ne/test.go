<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed, watch } from 'vue';
import UserDirectionController from '@/actions/App/Http/Controllers/UserDirectionController';
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
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
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
            class="sm:max-w-lg"
            :show-close-button="! required"
            @pointer-down-outside="required ? $event.preventDefault() : undefined"
            @escape-key-down="required ? $event.preventDefault() : undefined"
        >
            <DialogHeader>
                <DialogTitle>
                    {{
                        required
                            ? 'Выберите направление'
                            : 'Сменить направление'
                    }}
                </DialogTitle>
                <DialogDescription>
                    {{
                        required
                            ? 'Для работы с платформой укажите профильное направление ЕНТ.'
                            : 'Выберите новое направление. Оно будет использоваться при генерации экзаменов.'
                    }}
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-2">
                    <Label for="direction_id">Направление</Label>
                    <select
                        id="direction_id"
                        v-model="form.direction_id"
                        required
                        class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                    >
                        <option :value="null" disabled>
                            Выберите направление
                        </option>
                        <option
                            v-for="direction in directions"
                            :key="direction.id"
                            :value="direction.id"
                        >
                            {{ direction.code }} — {{ direction.name }}
                            ({{
                                direction.subjects
                                    .map((subject) => subject.name)
                                    .join(' + ')
                            }})
                        </option>
                    </select>
                    <InputError :message="form.errors.direction_id" />
                </div>

                <DialogFooter>
                    <Button
                        v-if="! required"
                        type="button"
                        variant="outline"
                        @click="open = false"
                    >
                        Отмена
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        Сохранить
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
