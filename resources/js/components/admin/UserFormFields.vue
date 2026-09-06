<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { emailFromPhone, formatPhoneInput, normalizePhoneDigits } from '@/lib/userContact';
import type { UserRole } from '@/types/auth';

type RoleOption = {
    value: UserRole;
    label: string;
};

type RegionOption = {
    id: number;
    name: string;
};

type DistrictOption = {
    id: number;
    name: string;
    region_id: number;
};

const props = withDefaults(
    defineProps<{
        errors: Record<string, string>;
        roles: RoleOption[];
        regions: RegionOption[];
        districts: DistrictOption[];
        mode?: 'create' | 'edit';
        initialName?: string;
        initialIin?: string;
        initialPhone?: string;
        initialRole?: UserRole;
        initialRegionId?: number | null;
        initialDistrictId?: number | null;
    }>(),
    {
        mode: 'create',
        initialName: '',
        initialIin: '',
        initialPhone: '',
        initialRegionId: null,
        initialDistrictId: null,
    },
);

const isEdit = computed(() => props.mode === 'edit');

const phoneDisplay = ref(
    props.initialPhone ? formatPhoneInput(props.initialPhone) : '',
);

const selectedRegionId = ref<number | ''>(
    props.initialRegionId ? props.initialRegionId : '',
);

const selectedDistrictId = ref<number | ''>(
    props.initialDistrictId ? props.initialDistrictId : '',
);

const generatedEmail = computed(() => emailFromPhone(phoneDisplay.value));

const normalizedPhone = computed(() => normalizePhoneDigits(phoneDisplay.value));

const filteredDistricts = computed(() => {
    if (! selectedRegionId.value) {
        return [];
    }

    return props.districts.filter(
        (district) => district.region_id === selectedRegionId.value,
    );
});

watch(phoneDisplay, (value) => {
    const formatted = formatPhoneInput(value);

    if (formatted !== value) {
        phoneDisplay.value = formatted;
    }
});

watch(selectedRegionId, (regionId) => {
    if (! regionId) {
        selectedDistrictId.value = '';

        return;
    }

    if (
        selectedDistrictId.value &&
        ! filteredDistricts.value.some(
            (district) => district.id === selectedDistrictId.value,
        )
    ) {
        selectedDistrictId.value = '';
    }
});

const selectClass =
    'flex h-9 w-full rounded-md border border-input bg-transparent px-3 py-1 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50';

const fieldClass = 'grid gap-1.5';
const inputClass = 'h-9';
</script>

<template>
    <div class="grid gap-4">
        <div :class="fieldClass">
            <Label for="name">ФИО</Label>
            <Input
                id="name"
                name="name"
                :class="inputClass"
                :default-value="initialName"
                placeholder="Иванов Иван Иванович"
                required
                autocomplete="name"
            />
            <InputError :message="errors.name" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div :class="fieldClass">
                <Label for="iin">ИИН</Label>
                <Input
                    id="iin"
                    name="iin"
                    :class="inputClass"
                    :default-value="initialIin"
                    inputmode="numeric"
                    maxlength="12"
                    placeholder="12 цифр"
                    required
                    autocomplete="off"
                />
                <InputError :message="errors.iin" />
            </div>

            <div :class="fieldClass">
                <Label for="phone">Телефон</Label>
                <Input
                    id="phone"
                    v-model="phoneDisplay"
                    :class="inputClass"
                    inputmode="tel"
                    placeholder="+7 (777) 123-45-67"
                    required
                    autocomplete="tel"
                />
                <input type="hidden" name="phone" :value="normalizedPhone" />
                <InputError :message="errors.phone" />
            </div>
        </div>

        <div
            class="rounded-md border border-dashed bg-muted/20 px-3 py-2 text-sm"
        >
            <span class="text-muted-foreground">Email:&nbsp;</span>
            <span class="font-medium">
                {{ generatedEmail || 'будет создан автоматически' }}
            </span>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div :class="fieldClass">
                <Label for="region_id">Область</Label>
                <select
                    id="region_id"
                    name="region_id"
                    v-model="selectedRegionId"
                    :class="selectClass"
                >
                    <option value="">Не выбрано</option>
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

            <div :class="fieldClass">
                <Label for="district_id">Район</Label>
                <select
                    id="district_id"
                    name="district_id"
                    v-model="selectedDistrictId"
                    :class="selectClass"
                    :disabled="! selectedRegionId"
                >
                    <option value="">Не выбрано</option>
                    <option
                        v-for="district in filteredDistricts"
                        :key="district.id"
                        :value="district.id"
                    >
                        {{ district.name }}
                    </option>
                </select>
                <InputError :message="errors.district_id" />
            </div>
        </div>

        <div :class="fieldClass">
            <Label for="role">Роль</Label>
            <select
                id="role"
                name="role"
                required
                :class="selectClass"
            >
                <option
                    v-for="role in roles"
                    :key="role.value"
                    :value="role.value"
                    :selected="initialRole === role.value"
                >
                    {{ role.label }}
                </option>
            </select>
            <InputError :message="errors.role" />
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div :class="fieldClass">
                <Label for="password">
                    {{ isEdit ? 'Новый пароль' : 'Пароль' }}
                </Label>
                <PasswordInput
                    id="password"
                    name="password"
                    :required="!isEdit"
                    autocomplete="new-password"
                    :placeholder="
                        isEdit ? 'Оставьте пустым, чтобы не менять' : undefined
                    "
                />
                <InputError :message="errors.password" />
            </div>

            <div :class="fieldClass">
                <Label for="password_confirmation">
                    Подтверждение пароля
                </Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    :required="!isEdit"
                    autocomplete="new-password"
                />
            </div>
        </div>
    </div>
</template>
