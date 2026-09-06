<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import ExamController from '@/actions/App/Http/Controllers/Admin/ExamController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit, index } from '@/routes/admin/exams';

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

type ExamForm = {
    id: number;
    title: string;
    description: string | null;
    status: string;
    generation_mode: string;
    direction_id: number | null;
    exam_blueprint_id: number | null;
    duration_minutes: number | null;
    starts_at: string | null;
    ends_at: string | null;
};

const props = defineProps<{
    exam: ExamForm;
    directions: DirectionOption[];
    blueprints: BlueprintOption[];
    statuses: StatusOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Экзамены', href: index() },
            { title: 'Изменить', href: edit(0) },
        ],
    },
});
</script>

<template>
    <Head title="Изменить экзамен" />

    <div class="mx-auto flex h-full max-w-2xl flex-1 flex-col gap-4 p-4 lg:p-6">
        <Heading
            title="Изменить экзамен"
            description="Обновите название, статус, даты и направление"
        />

        <Form
            v-bind="ExamController.update.form(props.exam.id)"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <input type="hidden" name="generation_mode" :value="exam.generation_mode" />
            <input
                type="hidden"
                name="exam_blueprint_id"
                :value="exam.exam_blueprint_id ?? ''"
            />

            <div class="grid gap-2">
                <Label for="title">Название</Label>
                <Input
                    id="title"
                    name="title"
                    :default-value="exam.title"
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
                    :default-value="exam.direction_id ?? ''"
                >
                    <option value="" disabled>Выберите направление</option>
                    <option
                        v-for="direction in directions"
                        :key="direction.id"
                        :value="direction.id"
                        :selected="direction.id === exam.direction_id"
                    >
                        {{ direction.code }} — {{ direction.name }}
                    </option>
                </select>
                <InputError :message="errors.direction_id" />
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
                        :selected="status.value === exam.status"
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
                        :default-value="exam.duration_minutes ?? 240"
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
                        :default-value="exam.starts_at ?? ''"
                    />
                    <InputError :message="errors.starts_at" />
                </div>
                <div class="grid gap-2">
                    <Label for="ends_at">Окончание</Label>
                    <Input
                        id="ends_at"
                        name="ends_at"
                        type="datetime-local"
                        :default-value="exam.ends_at ?? ''"
                    />
                    <InputError :message="errors.ends_at" />
                </div>
            </div>

            <div class="flex gap-3">
                <Button type="submit" :disabled="processing">
                    Сохранить
                </Button>
                <Button as-child variant="outline">
                    <Link :href="index()">Отмена</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
