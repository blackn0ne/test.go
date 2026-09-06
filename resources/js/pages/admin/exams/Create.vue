<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import ExamController from '@/actions/App/Http/Controllers/Admin/ExamController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index } from '@/routes/admin/exams';

type DirectionOption = {
    id: number;
    code: string;
    name: string;
    subjects: Array<{ id: number; name: string }>;
};

type BlueprintOption = {
    id: number;
    name: string;
    total_questions: number;
    is_default: boolean;
};

type StatusOption = { value: string; label: string };

const props = defineProps<{
    directions: DirectionOption[];
    blueprints: BlueprintOption[];
    statuses: StatusOption[];
}>();

const defaultBlueprintId =
    props.blueprints.find((item) => item.is_default)?.id ??
    props.blueprints[0]?.id ??
    '';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Экзамены', href: index() },
            { title: 'Создать', href: create() },
        ],
    },
});
</script>

<template>
    <Head title="Создать экзамен" />

    <div class="mx-auto flex h-full max-w-2xl flex-1 flex-col gap-4 p-4 lg:p-6">
        <Heading
            title="Создать экзамен"
            description="ЕНТ: 120 вопросов генерируются автоматически по направлению и шаблону"
        />

        <Form
            v-bind="ExamController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <input type="hidden" name="generation_mode" value="generated" />
            <input
                type="hidden"
                name="exam_blueprint_id"
                :value="defaultBlueprintId"
            />

            <div class="grid gap-2">
                <Label for="title">Название</Label>
                <Input
                    id="title"
                    name="title"
                    placeholder="ЕНТ — пробный, март 2026"
                    required
                />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="direction_id">Направление</Label>
                <select
                    id="direction_id"
                    name="direction_id"
                    required
                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                >
                    <option value="" disabled>Выберите направление</option>
                    <option
                        v-for="direction in directions"
                        :key="direction.id"
                        :value="direction.id"
                    >
                        {{ direction.code }} — {{ direction.name }}
                        ({{ direction.subjects.map((s) => s.name).join(' + ') }})
                    </option>
                </select>
                <InputError :message="errors.direction_id" />
            </div>

            <div class="grid gap-2">
                <Label for="blueprint_display">Шаблон</Label>
                <select
                    id="blueprint_display"
                    disabled
                    class="flex h-9 w-full rounded-md border border-input bg-muted px-3 py-1 text-sm"
                >
                    <option
                        v-for="blueprint in blueprints"
                        :key="blueprint.id"
                        :selected="blueprint.id === defaultBlueprintId"
                    >
                        {{ blueprint.name }} ({{ blueprint.total_questions }})
                    </option>
                </select>
                <p class="text-xs text-muted-foreground">
                    Оқу 10 + Мат. 10 + Тарих 20 + 2 профильных по 40
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="status">Статус</Label>
                <select
                    id="status"
                    name="status"
                    required
                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                >
                    <option
                        v-for="status in statuses"
                        :key="status.value"
                        :value="status.value"
                    >
                        {{ status.label }}
                    </option>
                </select>
                <InputError :message="errors.status" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="duration_minutes">Длительность (мин)</Label>
                    <Input
                        id="duration_minutes"
                        name="duration_minutes"
                        type="number"
                        min="1"
                        value="240"
                    />
                    <InputError :message="errors.duration_minutes" />
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="starts_at">Начало</Label>
                    <Input
                        id="starts_at"
                        name="starts_at"
                        type="datetime-local"
                    />
                    <InputError :message="errors.starts_at" />
                </div>
                <div class="grid gap-2">
                    <Label for="ends_at">Окончание</Label>
                    <Input
                        id="ends_at"
                        name="ends_at"
                        type="datetime-local"
                    />
                    <InputError :message="errors.ends_at" />
                </div>
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="processing">
                    Создать
                </Button>
                <Button as-child variant="outline">
                    <Link :href="index()">Отмена</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
