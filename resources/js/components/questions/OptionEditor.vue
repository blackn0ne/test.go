<script setup lang="ts">
import RichEditor from '@/components/RichEditor.vue';

withDefaults(
    defineProps<{
        placeholder?: string;
    }>(),
    {
        placeholder: 'Текст варианта...',
    },
);

const model = defineModel<string>({ default: '' });
</script>

<template>
    <RichEditor
        v-model="model"
        :placeholder="placeholder"
        compact
        minimal
    />
</template>
