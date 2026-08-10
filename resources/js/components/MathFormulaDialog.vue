<script setup lang="ts">
import katex from 'katex';
import { computed, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import 'katex/dist/katex.min.css';

const open = defineModel<boolean>('open', { default: false });

const emit = defineEmits<{
    insert: [latex: string, displayMode: boolean];
}>();

const latex = ref('');
const displayMode = ref(false);

const previewHtml = computed(() => {
    if (!latex.value.trim()) {
        return '';
    }

    try {
        return katex.renderToString(latex.value, {
            throwOnError: false,
            displayMode: displayMode.value,
        });
    } catch {
        return latex.value;
    }
});

watch(open, (isOpen) => {
    if (!isOpen) {
        latex.value = '';
        displayMode.value = false;
    }
});

function confirm(): void {
    if (!latex.value.trim()) {
        return;
    }

    emit('insert', latex.value.trim(), displayMode.value);
    open.value = false;
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Формула LaTeX</DialogTitle>
                <DialogDescription>
                    Пример: <code>x^2</code>, <code>\frac{a}{b}</code>,
                    <code>\sqrt{2}</code>
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4">
                <div class="grid gap-2">
                    <Label for="latex">LaTeX</Label>
                    <Input
                        id="latex"
                        v-model="latex"
                        placeholder="x^2 + y^2 = r^2"
                        @keydown.enter.prevent="confirm"
                    />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input
                        v-model="displayMode"
                        type="checkbox"
                        class="size-4 accent-primary"
                    />
                    Блочная формула (по центру)
                </label>

                <div
                    v-if="previewHtml"
                    class="min-h-16 rounded-lg border bg-muted/30 p-4"
                    v-html="previewHtml"
                />
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="open = false">
                    Отмена
                </Button>
                <Button type="button" :disabled="!latex.trim()" @click="confirm">
                    Вставить
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
