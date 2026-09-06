<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import DirectoryController from '@/actions/App/Http/Controllers/Admin/DirectoryController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index as adminDirectories } from '@/routes/admin/directories';
import { cn } from '@/lib/utils';

type SchoolClassItem = {
    id: number;
    name: string;
    sort_order: number;
};

type SubjectItem = {
    id: number;
    name: string;
    code: string | null;
    kind: string;
    is_system: boolean;
    school_classes: Array<{ id: number; name: string }>;
};

type DirectionItem = {
    id: number;
    code: string;
    name: string;
    subjects: Array<{ id: number; name: string }>;
};

type ProfileSubjectItem = {
    id: number;
    name: string;
};

type GroupItem = {
    id: number;
    name: string;
    subject_id: number;
    subject: { id: number; name: string };
};

type RegionItem = {
    id: number;
    name: string;
    sort_order: number;
    districts_count: number;
};

type DistrictItem = {
    id: number;
    name: string;
    region_id: number;
    region: { id: number; name: string };
};

const props = defineProps<{
    tab: string;
    classes: SchoolClassItem[];
    subjects: SubjectItem[];
    coreSubjectCodes: Record<string, string>;
    profileSubjects: ProfileSubjectItem[];
    directions: DirectionItem[];
    groups: GroupItem[];
    regions: RegionItem[];
    districts: DistrictItem[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Справочники',
                href: adminDirectories(),
            },
        ],
    },
});

const tabs = [
    { key: 'classes', label: 'Классы' },
    { key: 'subjects', label: 'Предметы' },
    { key: 'directions', label: 'Направления' },
    { key: 'groups', label: 'Группы' },
    { key: 'regions', label: 'Области' },
    { key: 'districts', label: 'Районы' },
] as const;

const activeTab = computed(() => props.tab || 'classes');

const editingClassId = ref<number | null>(null);
const editingSubjectId = ref<number | null>(null);
const editingDirectionId = ref<number | null>(null);
const editingGroupId = ref<number | null>(null);
const editingRegionId = ref<number | null>(null);
const editingDistrictId = ref<number | null>(null);

const newClassName = ref('');
const newClassSortOrder = ref('0');
const newSubjectName = ref('');
const newSubjectClassIds = ref<number[]>([]);
const newGroupName = ref('');
const newGroupSubjectId = ref<number | ''>('');
const newDirectionCode = ref('');
const newDirectionName = ref('');
const newFirstSubjectId = ref<number | ''>('');
const newSecondSubjectId = ref<number | ''>('');
const newRegionName = ref('');
const newRegionSortOrder = ref('0');
const newDistrictName = ref('');
const newDistrictRegionId = ref<number | ''>('');

function tabHref(tab: string) {
    return adminDirectories({ query: { tab } });
}

function suggestedCodeForSubject(name: string): string {
    const entry = Object.entries(props.coreSubjectCodes).find(
        ([, canonicalName]) => canonicalName === name,
    );

    return entry?.[0] ?? '';
}

function toggleSubjectClass(classId: number, checked: boolean) {
    if (checked) {
        if (! newSubjectClassIds.value.includes(classId)) {
            newSubjectClassIds.value.push(classId);
        }
    } else {
        newSubjectClassIds.value = newSubjectClassIds.value.filter(
            (id) => id !== classId,
        );
    }
}
</script>

<template>
    <Head title="Справочники" />

    <div class="flex h-full flex-1 flex-col gap-4 p-4">
        <Heading
            title="Справочники"
            description="Классы, предметы, направления, группы, области и районы"
        />

        <div class="flex flex-wrap gap-2 border-b pb-2">
            <Link
                v-for="tabItem in tabs"
                :key="tabItem.key"
                :href="tabHref(tabItem.key)"
                :class="
                    cn(
                        'rounded-md px-3 py-1.5 text-sm font-medium transition-colors',
                        activeTab === tabItem.key
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-muted hover:text-foreground',
                    )
                "
            >
                {{ tabItem.label }}
            </Link>
        </div>

        <div v-if="activeTab === 'classes'" class="space-y-4">
            <Card>
                <CardHeader>
                    <CardTitle>Добавить класс</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="DirectoryController.storeClass.form()"
                        class="grid gap-4 md:grid-cols-[1fr_120px_auto]"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="class_name">Название</Label>
                            <Input
                                id="class_name"
                                name="name"
                                v-model="newClassName"
                                placeholder="Например: 10А"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="class_sort">Порядок</Label>
                            <Input
                                id="class_sort"
                                name="sort_order"
                                type="number"
                                min="0"
                                v-model="newClassSortOrder"
                            />
                        </div>
                        <div class="flex items-end">
                            <Button type="submit" :disabled="processing">
                                Добавить
                            </Button>
                        </div>
                    </Form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Список классов</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="schoolClass in classes"
                        :key="schoolClass.id"
                        class="rounded-lg border p-4"
                    >
                        <Form
                            v-if="editingClassId === schoolClass.id"
                            v-bind="
                                DirectoryController.updateClass.form(
                                    schoolClass.id,
                                )
                            "
                            class="grid gap-3 md:grid-cols-[1fr_120px_auto_auto]"
                            v-slot="{ errors, processing }"
                        >
                            <Input
                                name="name"
                                :default-value="schoolClass.name"
                                required
                            />
                            <Input
                                name="sort_order"
                                type="number"
                                min="0"
                                :default-value="String(schoolClass.sort_order)"
                            />
                            <Button type="submit" size="sm" :disabled="processing">
                                Сохранить
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="editingClassId = null"
                            >
                                Отмена
                            </Button>
                            <InputError :message="errors.name" />
                        </Form>

                        <div
                            v-else
                            class="flex items-center justify-between gap-3"
                        >
                            <div>
                                <p class="font-medium">{{ schoolClass.name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    Порядок: {{ schoolClass.sort_order }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="editingClassId = schoolClass.id"
                                >
                                    Изменить
                                </Button>
                                <Form
                                    v-bind="
                                        DirectoryController.destroyClass.form(
                                            schoolClass.id,
                                        )
                                    "
                                >
                                    <Button
                                        type="submit"
                                        size="sm"
                                        variant="destructive"
                                    >
                                        Удалить
                                    </Button>
                                </Form>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div v-else-if="activeTab === 'subjects'" class="space-y-4">
            <Card>
                <CardHeader>
                    <CardTitle>Добавить предмет</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="DirectoryController.storeSubject.form()"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="subject_name">Название</Label>
                            <Input
                                id="subject_name"
                                name="name"
                                v-model="newSubjectName"
                                placeholder="Например: Математика"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>

                        <div class="grid gap-2">
                            <Label>Классы</Label>
                            <div
                                v-if="classes.length"
                                class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3"
                            >
                                <label
                                    v-for="schoolClass in classes"
                                    :key="schoolClass.id"
                                    class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        name="school_class_ids[]"
                                        :value="schoolClass.id"
                                        @change="
                                            toggleSubjectClass(
                                                schoolClass.id,
                                                (
                                                    $event.target as HTMLInputElement
                                                ).checked,
                                            )
                                        "
                                    />
                                    {{ schoolClass.name }}
                                </label>
                            </div>
                            <p v-else class="text-sm text-muted-foreground">
                                Сначала добавьте классы
                            </p>
                            <InputError :message="errors.school_class_ids" />
                        </div>

                        <Button type="submit" :disabled="processing">
                            Добавить
                        </Button>
                    </Form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Список предметов</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="subject in subjects"
                        :key="subject.id"
                        class="rounded-lg border p-4"
                    >
                        <Form
                            v-if="editingSubjectId === subject.id"
                            v-bind="
                                DirectoryController.updateSubject.form(
                                    subject.id,
                                )
                            "
                            class="space-y-3"
                            v-slot="{ errors, processing }"
                        >
                            <div class="grid gap-2">
                                <Label>Название</Label>
                                <Input
                                    name="name"
                                    :default-value="subject.name"
                                    required
                                />
                                <InputError :message="errors.name" />
                            </div>

                            <div class="grid gap-2">
                                <Label>Код</Label>
                                <Input
                                    name="code"
                                    :default-value="
                                        subject.code ??
                                        suggestedCodeForSubject(subject.name)
                                    "
                                    placeholder="reading_literacy"
                                    :required="
                                        subject.kind === 'core' ||
                                        subject.is_system
                                    "
                                />
                                <p class="text-xs text-muted-foreground">
                                    Латиница, цифры, дефис. Для ЕНТ:
                                    reading_literacy, math_literacy,
                                    kazakhstan_history
                                </p>
                                <InputError :message="errors.code" />
                            </div>

                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-if="subject.is_system"
                                    type="hidden"
                                    name="is_core"
                                    value="1"
                                />
                                <input
                                    type="checkbox"
                                    name="is_core"
                                    value="1"
                                    :checked="
                                        subject.kind === 'core' ||
                                        subject.is_system
                                    "
                                    :disabled="subject.is_system"
                                />
                                Обязательный (ЕНТ)
                            </label>
                            <p
                                v-if="subject.is_system"
                                class="text-xs text-muted-foreground"
                            >
                                Системный предмет нельзя снять с ЕНТ, пока он в
                                шаблоне
                            </p>
                            <div class="grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                                <label
                                    v-for="schoolClass in classes"
                                    :key="schoolClass.id"
                                    class="flex items-center gap-2 rounded-md border px-3 py-2 text-sm"
                                >
                                    <input
                                        type="checkbox"
                                        name="school_class_ids[]"
                                        :value="schoolClass.id"
                                        :checked="
                                            subject.school_classes.some(
                                                (item) =>
                                                    item.id === schoolClass.id,
                                            )
                                        "
                                    />
                                    {{ schoolClass.name }}
                                </label>
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    type="submit"
                                    size="sm"
                                    :disabled="processing"
                                >
                                    Сохранить
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    @click="editingSubjectId = null"
                                >
                                    Отмена
                                </Button>
                            </div>
                            <InputError :message="errors.school_class_ids" />
                        </Form>

                        <div v-else class="space-y-2">
                            <div
                                class="flex flex-wrap items-start justify-between gap-3"
                            >
                                <div class="min-w-0 flex-1">
                                    <p class="font-medium">{{ subject.name }}</p>
                                    <p
                                        v-if="subject.code"
                                        class="text-xs text-muted-foreground"
                                    >
                                        {{ subject.code }}
                                    </p>
                                    <Badge
                                        v-if="subject.is_system"
                                        class="mt-1"
                                        variant="outline"
                                    >
                                        Системный
                                    </Badge>
                                </div>
                                <div class="flex shrink-0 gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        @click="editingSubjectId = subject.id"
                                    >
                                        Изменить
                                    </Button>
                                    <Form
                                        v-if="! subject.is_system"
                                        v-bind="
                                            DirectoryController.destroySubject.form(
                                                subject.id,
                                            )
                                        "
                                    >
                                        <Button
                                            type="submit"
                                            size="sm"
                                            variant="destructive"
                                        >
                                            Удалить
                                        </Button>
                                    </Form>
                                </div>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <Badge
                                    v-for="schoolClass in subject.school_classes"
                                    :key="schoolClass.id"
                                    variant="secondary"
                                >
                                    {{ schoolClass.name }}
                                </Badge>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div v-else-if="activeTab === 'directions'" class="space-y-4">
            <Card>
                <CardHeader>
                    <CardTitle>Добавить направление</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="DirectoryController.storeDirection.form()"
                        class="space-y-4"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="direction_code">Код</Label>
                                <Input
                                    id="direction_code"
                                    name="code"
                                    v-model="newDirectionCode"
                                    placeholder="FIZ-MAT"
                                    required
                                />
                                <InputError :message="errors.code" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="direction_name">Название</Label>
                                <Input
                                    id="direction_name"
                                    name="name"
                                    v-model="newDirectionName"
                                    placeholder="Физика + Математика"
                                    required
                                />
                                <InputError :message="errors.name" />
                            </div>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="first_subject_id">Предмет 1</Label>
                                <select
                                    id="first_subject_id"
                                    name="first_subject_id"
                                    required
                                    v-model="newFirstSubjectId"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                                >
                                    <option value="" disabled>Выберите</option>
                                    <option
                                        v-for="subject in profileSubjects"
                                        :key="subject.id"
                                        :value="subject.id"
                                    >
                                        {{ subject.name }}
                                    </option>
                                </select>
                                <InputError :message="errors.first_subject_id" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="second_subject_id">Предмет 2</Label>
                                <select
                                    id="second_subject_id"
                                    name="second_subject_id"
                                    required
                                    v-model="newSecondSubjectId"
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                                >
                                    <option value="" disabled>Выберите</option>
                                    <option
                                        v-for="subject in profileSubjects"
                                        :key="subject.id"
                                        :value="subject.id"
                                    >
                                        {{ subject.name }}
                                    </option>
                                </select>
                                <InputError :message="errors.second_subject_id" />
                            </div>
                        </div>
                        <Button type="submit" :disabled="processing">
                            Добавить
                        </Button>
                    </Form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Направления</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="direction in directions"
                        :key="direction.id"
                        class="rounded-lg border p-4"
                    >
                        <Form
                            v-if="editingDirectionId === direction.id"
                            v-bind="
                                DirectoryController.updateDirection.form(
                                    direction.id,
                                )
                            "
                            class="space-y-3"
                            v-slot="{ errors, processing }"
                        >
                            <div class="grid gap-4 sm:grid-cols-2">
                                <Input
                                    name="code"
                                    :default-value="direction.code"
                                    required
                                />
                                <Input
                                    name="name"
                                    :default-value="direction.name"
                                    required
                                />
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <select
                                    name="first_subject_id"
                                    required
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                                    :default-value="direction.subjects[0]?.id"
                                >
                                    <option
                                        v-for="subject in profileSubjects"
                                        :key="subject.id"
                                        :value="subject.id"
                                        :selected="
                                            direction.subjects[0]?.id ===
                                            subject.id
                                        "
                                    >
                                        {{ subject.name }}
                                    </option>
                                </select>
                                <select
                                    name="second_subject_id"
                                    required
                                    class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs"
                                >
                                    <option
                                        v-for="subject in profileSubjects"
                                        :key="subject.id"
                                        :value="subject.id"
                                        :selected="
                                            direction.subjects[1]?.id ===
                                            subject.id
                                        "
                                    >
                                        {{ subject.name }}
                                    </option>
                                </select>
                            </div>
                            <div class="flex gap-2">
                                <Button type="submit" size="sm" :disabled="processing">
                                    Сохранить
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    @click="editingDirectionId = null"
                                >
                                    Отмена
                                </Button>
                            </div>
                            <InputError :message="errors.code" />
                        </Form>
                        <div v-else class="flex items-center justify-between gap-3">
                            <div>
                                <p class="font-medium">
                                    {{ direction.code }} — {{ direction.name }}
                                </p>
                                <p class="text-sm text-muted-foreground">
                                    {{
                                        direction.subjects
                                            .map((s) => s.name)
                                            .join(' + ')
                                    }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="editingDirectionId = direction.id"
                                >
                                    Изменить
                                </Button>
                                <Form
                                    v-bind="
                                        DirectoryController.destroyDirection.form(
                                            direction.id,
                                        )
                                    "
                                >
                                    <Button
                                        type="submit"
                                        size="sm"
                                        variant="destructive"
                                    >
                                        Удалить
                                    </Button>
                                </Form>
                            </div>
                        </div>
                    </div>
                    <p
                        v-if="directions.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Сначала добавьте профильные предметы, затем направления
                    </p>
                </CardContent>
            </Card>
        </div>

        <div v-else-if="activeTab === 'groups'" class="space-y-4">
            <Card>
                <CardHeader>
                    <CardTitle>Добавить группу</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="DirectoryController.storeGroup.form()"
                        class="grid gap-4 md:grid-cols-[1fr_1fr_auto]"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="group_name">Название</Label>
                            <Input
                                id="group_name"
                                name="name"
                                v-model="newGroupName"
                                placeholder="Например: Группа A"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="group_subject">Предмет</Label>
                            <select
                                id="group_subject"
                                name="subject_id"
                                required
                                v-model="newGroupSubjectId"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="" disabled>
                                    Выберите предмет
                                </option>
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
                        <div class="flex items-end">
                            <Button type="submit" :disabled="processing">
                                Добавить
                            </Button>
                        </div>
                    </Form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Список групп</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="group in groups"
                        :key="group.id"
                        class="rounded-lg border p-4"
                    >
                        <Form
                            v-if="editingGroupId === group.id"
                            v-bind="
                                DirectoryController.updateGroup.form(group.id)
                            "
                            class="grid gap-3 md:grid-cols-[1fr_1fr_auto_auto]"
                            v-slot="{ errors, processing }"
                        >
                            <Input
                                name="name"
                                :default-value="group.name"
                                required
                            />
                            <select
                                name="subject_id"
                                required
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                                :default-value="String(group.subject_id)"
                            >
                                <option
                                    v-for="subject in subjects"
                                    :key="subject.id"
                                    :value="subject.id"
                                    :selected="subject.id === group.subject_id"
                                >
                                    {{ subject.name }}
                                </option>
                            </select>
                            <Button type="submit" size="sm" :disabled="processing">
                                Сохранить
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="editingGroupId = null"
                            >
                                Отмена
                            </Button>
                            <InputError :message="errors.name" />
                        </Form>

                        <div
                            v-else
                            class="flex items-center justify-between gap-3"
                        >
                            <div>
                                <p class="font-medium">{{ group.name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ group.subject.name }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="editingGroupId = group.id"
                                >
                                    Изменить
                                </Button>
                                <Form
                                    v-bind="
                                        DirectoryController.destroyGroup.form(
                                            group.id,
                                        )
                                    "
                                >
                                    <Button
                                        type="submit"
                                        size="sm"
                                        variant="destructive"
                                    >
                                        Удалить
                                    </Button>
                                </Form>
                            </div>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div v-else-if="activeTab === 'regions'" class="space-y-4">
            <Card>
                <CardHeader>
                    <CardTitle>Добавить область</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="DirectoryController.storeRegion.form()"
                        class="grid gap-4 md:grid-cols-[1fr_120px_auto]"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="region_name">Название</Label>
                            <Input
                                id="region_name"
                                name="name"
                                v-model="newRegionName"
                                placeholder="Например: Туркестанская область"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="region_sort">Порядок</Label>
                            <Input
                                id="region_sort"
                                name="sort_order"
                                type="number"
                                min="0"
                                v-model="newRegionSortOrder"
                            />
                        </div>
                        <div class="flex items-end">
                            <Button type="submit" :disabled="processing">
                                Добавить
                            </Button>
                        </div>
                    </Form>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Список областей</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="region in regions"
                        :key="region.id"
                        class="rounded-lg border p-4"
                    >
                        <Form
                            v-if="editingRegionId === region.id"
                            v-bind="
                                DirectoryController.updateRegion.form(region.id)
                            "
                            class="grid gap-3 md:grid-cols-[1fr_120px_auto_auto]"
                            v-slot="{ errors, processing }"
                        >
                            <Input
                                name="name"
                                :default-value="region.name"
                                required
                            />
                            <Input
                                name="sort_order"
                                type="number"
                                min="0"
                                :default-value="String(region.sort_order)"
                            />
                            <Button type="submit" size="sm" :disabled="processing">
                                Сохранить
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="editingRegionId = null"
                            >
                                Отмена
                            </Button>
                            <InputError :message="errors.name" />
                        </Form>
                        <div
                            v-else
                            class="flex items-center justify-between gap-3"
                        >
                            <div>
                                <p class="font-medium">{{ region.name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    Районов: {{ region.districts_count }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="editingRegionId = region.id"
                                >
                                    Изменить
                                </Button>
                                <Form
                                    v-bind="
                                        DirectoryController.destroyRegion.form(
                                            region.id,
                                        )
                                    "
                                >
                                    <Button
                                        type="submit"
                                        size="sm"
                                        variant="destructive"
                                    >
                                        Удалить
                                    </Button>
                                </Form>
                            </div>
                        </div>
                    </div>
                    <p
                        v-if="regions.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Областей пока нет
                    </p>
                </CardContent>
            </Card>
        </div>

        <div v-else-if="activeTab === 'districts'" class="space-y-4">
            <Card>
                <CardHeader>
                    <CardTitle>Добавить район</CardTitle>
                </CardHeader>
                <CardContent>
                    <Form
                        v-bind="DirectoryController.storeDistrict.form()"
                        class="grid gap-4 md:grid-cols-[1fr_1fr_auto]"
                        v-slot="{ errors, processing }"
                    >
                        <div class="grid gap-2">
                            <Label for="district_name">Название</Label>
                            <Input
                                id="district_name"
                                name="name"
                                v-model="newDistrictName"
                                placeholder="Например: Сауран ауданы"
                                required
                            />
                            <InputError :message="errors.name" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="district_region">Область</Label>
                            <select
                                id="district_region"
                                name="region_id"
                                required
                                v-model="newDistrictRegionId"
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="" disabled>
                                    Выберите область
                                </option>
                                <option
                                    v-for="region in regions"
                                    :key="region.id"
                                    :value="region.id"
                                >
                                    {{ region.name }}
                                </option>
                            </select>
                            <InputError :message="errors.region_id" />
                        </div>
                        <div class="flex items-end">
                            <Button
                                type="submit"
                                :disabled="processing || regions.length === 0"
                            >
                                Добавить
                            </Button>
                        </div>
                    </Form>
                    <p
                        v-if="regions.length === 0"
                        class="mt-3 text-sm text-muted-foreground"
                    >
                        Сначала добавьте область, затем районы для неё
                    </p>
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle>Список районов</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="district in districts"
                        :key="district.id"
                        class="rounded-lg border p-4"
                    >
                        <Form
                            v-if="editingDistrictId === district.id"
                            v-bind="
                                DirectoryController.updateDistrict.form(
                                    district.id,
                                )
                            "
                            class="grid gap-3 md:grid-cols-[1fr_1fr_auto_auto]"
                            v-slot="{ errors, processing }"
                        >
                            <Input
                                name="name"
                                :default-value="district.name"
                                required
                            />
                            <select
                                name="region_id"
                                required
                                class="flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none"
                            >
                                <option
                                    v-for="region in regions"
                                    :key="region.id"
                                    :value="region.id"
                                    :selected="region.id === district.region_id"
                                >
                                    {{ region.name }}
                                </option>
                            </select>
                            <Button type="submit" size="sm" :disabled="processing">
                                Сохранить
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                @click="editingDistrictId = null"
                            >
                                Отмена
                            </Button>
                            <InputError :message="errors.name" />
                        </Form>
                        <div
                            v-else
                            class="flex items-center justify-between gap-3"
                        >
                            <div>
                                <p class="font-medium">{{ district.name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ district.region.name }}
                                </p>
                            </div>
                            <div class="flex gap-2">
                                <Button
                                    size="sm"
                                    variant="outline"
                                    @click="editingDistrictId = district.id"
                                >
                                    Изменить
                                </Button>
                                <Form
                                    v-bind="
                                        DirectoryController.destroyDistrict.form(
                                            district.id,
                                        )
                                    "
                                >
                                    <Button
                                        type="submit"
                                        size="sm"
                                        variant="destructive"
                                    >
                                        Удалить
                                    </Button>
                                </Form>
                            </div>
                        </div>
                    </div>
                    <p
                        v-if="districts.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Районов пока нет
                    </p>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
