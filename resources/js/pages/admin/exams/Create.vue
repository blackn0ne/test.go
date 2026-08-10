<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import ExamController from '@/actions/App/Http/Controllers/Admin/ExamController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index } from '@/routes/admin/exams';

type SubjectOption = { id: number; name: string };
type StatusOption = { value: string; label: string };

defineProps<{
    subjects: SubjectOption[];
    statuses: StatusOption[];
}>();

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
            description="Выберите вопросы из банка. Правильные ответы студентам не передаются."
        />

        <Form
            v-bind="ExamController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <div class="grid gap-2">
                <Label for="title">Название</Label>
                <Input id="title" name="title" required />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="subject_id">Предмет</Label>
                <select
                    id="subject_id"
                    name="subject_id"
                    required
                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
                >
                    <option value="" disabled>Выберите предмет</option>
                    <option
                        v-for="subject in subjects"
                        :key="subject.id"
                        :value="subject.id"
                    >
                        {{ subject.name }}
                    </option>
                </select>
                <InputError :message="errors.subject_id" />
            </div>

            <div class="grid gap-2">
                <Label for="status">Статус</Label>
                <select
                    id="status"
                    name="status"
                    required
                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm"
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

            <div class="grid gap-2">
                <Label for="question_ids">ID вопросов (через запятую)</Label>
                <Input
                    id="question_ids"
                    name="question_ids"
                    placeholder="1, 2, 3"
                    required
                />
                <p class="text-xs text-muted-foreground">
                    Временный ввод до UI выбора из банка. Пример: 1,2,3
                </p>
                <InputError :message="errors.question_ids" />
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
