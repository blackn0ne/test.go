<script setup lang="ts">
import { Sigma } from '@lucide/vue';
import { nextTick, ref, watch } from 'vue';
import MathFormulaDialog from '@/components/MathFormulaDialog.vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import {
    htmlToPlainMathText,
    insertAtCursor,
    plainMathTextToHtml,
} from '@/lib/math';

type Props = {
    placeholder?: string;
    compact?: boolean;
};

withDefaults(defineProps<Props>(), {
    placeholder: 'Текст варианта...',
    compact: false,
});

const model = defineModel<string>({ default: '' });

const plainText = ref('');
const textareaRef = ref<HTMLTextAreaElement | null>(null);
const formulaDialogOpen = ref(false);
const selection = ref({ start: 0, end: 0 });

watch(
    () => model.value,
    (value) => {
        const nextPlain = htmlToPlainMathText(value);

        if (nextPlain !== plainText.value) {
            plainText.value = nextPlain;
        }
    },
    { immediate: true },
);

watch(plainText, (value) => {
    const nextHtml = plainMathTextToHtml(value);

    if (nextHtml !== model.value) {
        model.value = nextHtml;
    }
});

function rememberSelection(): void {
    if (!textareaRef.value) {
        return;
    }

    selection.value = {
        start: textareaRef.value.selectionStart,
        end: textareaRef.value.selectionEnd,
    };
}

async function insertFormula(latex: string, displayMode: boolean): Promise<void> {
    const wrapped = displayMode ? `$$${latex}$$` : `$${latex}$`;
    const { nextValue, nextCursor } = insertAtCursor(
        plainText.value,
        wrapped,
        selection.value.start,
        selection.value.end,
    );

    plainText.value = nextValue;

    await nextTick();

    if (textareaRef.value) {
        textareaRef.value.focus();
        textareaRef.value.setSelectionRange(nextCursor, nextCursor);
    }
}
</script>

<template>
    <div :class="compact ? 'flex min-w-0 flex-1 items-center gap-1.5' : 'space-y-2'">
        <textarea
            ref="textareaRef"
            v-model="plainText"
            :placeholder="placeholder"
            :rows="compact ? 1 : 3"
            :class="
                cn(
                    'placeholder:text-muted-foreground w-full resize-none rounded-md border border-input bg-background px-2.5 py-1.5 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50',
                    compact ? 'min-h-8 leading-normal' : 'min-h-24 resize-y shadow-xs',
                )
            "
            @click="rememberSelection"
            @keyup="rememberSelection"
            @mouseup="rememberSelection"
        />

        <Button
            type="button"
            size="icon"
            variant="ghost"
            :class="compact ? 'size-8 shrink-0 text-muted-foreground' : 'hidden'"
            title="Вставить формулу"
            @click="formulaDialogOpen = true"
        >
            <Sigma class="size-3.5" />
        </Button>

        <template v-if="!compact">
            <div class="flex items-center justify-between gap-2">
                <Button
                    type="button"
                    size="sm"
                    variant="outline"
                    class="gap-1"
                    @click="formulaDialogOpen = true"
                >
                    <Sigma class="size-4" />
                    Формула
                </Button>
                <p class="text-xs text-muted-foreground">
                    или <code>$x^2$</code> в тексте
                </p>
            </div>
        </template>

        <MathFormulaDialog
            v-model:open="formulaDialogOpen"
            @insert="insertFormula"
        />
    </div>
</template>
