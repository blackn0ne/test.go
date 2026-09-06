<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import PromoCodeController from '@/actions/App/Http/Controllers/Admin/PromoCodeController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { create, index } from '@/routes/admin/promo-codes';

type SchoolOption = {
    id: number;
    name: string;
    students_count: number;
};

type MonthOption = {
    value: number;
    label: string;
};

const props = defineProps<{
    schools: SchoolOption[];
    years: number[];
    months: MonthOption[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Промокоды', href: index() },
            { title: 'Сгенерировать', href: create() },
        ],
    },
});

const selectedSchoolId = ref<number | ''>('');
const selectedYear = ref<number>(props.years[0] ?? new Date().getFullYear());
const selectedMonth = ref<number>(new Date().getMonth() + 1);
const couponsPerStudent = ref('2');

const selectedSchool = computed(() =>
    props.schools.find((school) => school.id === selectedSchoolId.value),
);

const totalCodesPreview = computed(() => {
    if (! selectedSchool.value) {
        return 0;
    }

    const perStudent = Number.parseInt(couponsPerStudent.value, 10) || 0;

    return selectedSchool.value.students_count * perStudent;
});

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';
</script>

<template>
    <Head title="Сгенерировать промокоды" />

    <div class="mx-auto flex h-full max-w-2xl flex-1 flex-col gap-4 p-4 lg:p-6">
        <Heading
            title="Сгенерировать промокоды"
            description="5 символов: цифры и заглавные латинские буквы. Доступ к экзамену за выбранный месяц."
        />

        <Form
            v-bind="PromoCodeController.store.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
        >
            <Card>
                <CardHeader>
                    <CardTitle>Параметры генерации</CardTitle>
                </CardHeader>
                <CardContent class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="year">Год</Label>
                        <select
                            id="year"
                            name="year"
                            v-model="selectedYear"
                            required
                            :class="selectClass"
                        >
                            <option
                                v-for="year in years"
                                :key="year"
                                :value="year"
                            >
                                {{ year }}
                            </option>
                        </select>
                        <InputError :message="errors.year" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="month">Месяц</Label>
                        <select
                            id="month"
                            name="month"
                            v-model="selectedMonth"
                            required
                            :class="selectClass"
                        >
                            <option
                                v-for="month in months"
                                :key="month.value"
                                :value="month.value"
                            >
                                {{ month.label }}
                            </option>
                        </select>
                        <InputError :message="errors.month" />
                    </div>

                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="school_id">Школа</Label>
                        <select
                            id="school_id"
                            name="school_id"
                            v-model="selectedSchoolId"
                            required
                            :class="selectClass"
                        >
                            <option value="" disabled>Выберите школу</option>
                            <option
                                v-for="school in schools"
                                :key="school.id"
                                :value="school.id"
                            >
                                {{ school.name }} ({{ school.students_count }}
                                {{ school.students_count === 1 ? 'студент' : 'студентов' }})
                            </option>
                        </select>
                        <InputError :message="errors.school_id" />
                    </div>

                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="coupons_per_student">
                            Промокодов на одного студента
                        </Label>
                        <Input
                            id="coupons_per_student"
                            name="coupons_per_student"
                            type="number"
                            min="1"
                            max="100"
                            v-model="couponsPerStudent"
                            required
                        />
                        <InputError :message="errors.coupons_per_student" />
                    </div>

                    <div
                        v-if="selectedSchool"
                        class="rounded-md border border-dashed bg-muted/20 px-3 py-2 text-sm sm:col-span-2"
                    >
                        Будет сгенерировано:
                        <span class="font-semibold">{{ totalCodesPreview }}</span>
                        промокодов ({{ selectedSchool.students_count }} ×
                        {{ couponsPerStudent }})
                    </div>
                </CardContent>
            </Card>

            <div class="flex flex-wrap gap-2">
                <Button type="submit" :disabled="processing || totalCodesPreview === 0">
                    Сгенерировать
                </Button>
                <Button as-child variant="outline">
                    <Link :href="index()">Отмена</Link>
                </Button>
            </div>
        </Form>
    </div>
</template>
