<script setup lang="ts">
import Image from '@tiptap/extension-image';
import Mathematics from '@tiptap/extension-mathematics';
import Placeholder from '@tiptap/extension-placeholder';
import { EditorContent, useEditor } from '@tiptap/vue-3';
import StarterKit from '@tiptap/starter-kit';
import {
    Bold,
    ImagePlus,
    Italic,
    List,
    ListOrdered,
    Redo2,
    Sigma,
    Undo2,
} from '@lucide/vue';
import type { Editor } from '@tiptap/core';
import { onBeforeUnmount, ref, watch } from 'vue';
import MathFormulaDialog from '@/components/MathFormulaDialog.vue';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { insertUploadedImage } from '@/lib/editorImages';
import 'katex/dist/katex.min.css';

type Props = {
    placeholder?: string;
    compact?: boolean;
    minimal?: boolean;
};

const props = withDefaults(defineProps<Props>(), {
    placeholder: 'Введите текст...',
    compact: false,
    minimal: false,
});

const model = defineModel<string>({ default: '' });

const formulaDialogOpen = ref(false);
const fileInputRef = ref<HTMLInputElement | null>(null);
const uploading = ref(false);
const editorInstance = ref<Editor | null>(null);

function insertImageFromFile(editor: Editor, file: File): void {
    uploading.value = true;

    insertUploadedImage((attrs) => {
        editor.chain().focus().setImage(attrs).run();
    }, file)
        .catch(() => {
            window.alert('Не удалось загрузить изображение.');
        })
        .finally(() => {
            uploading.value = false;
        });
}

function createPasteHandler(editor: Editor) {
    return (_view: unknown, event: ClipboardEvent): boolean => {
        const items = event.clipboardData?.items;

        if (!items) {
            return false;
        }

        for (const item of items) {
            if (!item.type.startsWith('image/')) {
                continue;
            }

            event.preventDefault();
            const file = item.getAsFile();

            if (file) {
                insertImageFromFile(editor, file);
            }

            return true;
        }

        return false;
    };
}

function createDropHandler(editor: Editor) {
    return (_view: unknown, event: DragEvent): boolean => {
        const file = event.dataTransfer?.files?.[0];

        if (!file || !file.type.startsWith('image/')) {
            return false;
        }

        event.preventDefault();
        insertImageFromFile(editor, file);

        return true;
    };
}

const editor = useEditor({
    content: model.value,
    extensions: [
        StarterKit.configure(
            props.minimal ? { heading: false } : {},
        ),
        Placeholder.configure({
            placeholder: props.placeholder,
        }),
        Mathematics.configure({
            katexOptions: {
                throwOnError: false,
            },
        }),
        Image.configure({
            inline: true,
            allowBase64: false,
        }),
    ],
    editorProps: {
        attributes: {
            class: cn(
                'tiptap-editor focus:outline-none',
                props.compact
                    ? 'min-h-10 px-2 py-1.5 text-sm'
                    : 'min-h-32 px-3 py-2',
            ),
        },
        handlePaste: (_view, event) => {
            if (!editorInstance.value) {
                return false;
            }

            return createPasteHandler(editorInstance.value)(null, event);
        },
        handleDrop: (_view, event) => {
            if (!editorInstance.value) {
                return false;
            }

            return createDropHandler(editorInstance.value)(null, event);
        },
    },
    onCreate: ({ editor: createdEditor }) => {
        editorInstance.value = createdEditor;
    },
    onUpdate: ({ editor: currentEditor }) => {
        model.value = currentEditor.getHTML();
    },
});

watch(
    () => model.value,
    (value) => {
        if (!editor.value) {
            return;
        }

        if (editor.value.getHTML() === value) {
            return;
        }

        editor.value.commands.setContent(value || '', { emitUpdate: false });
    },
);

onBeforeUnmount(() => {
    editor.value?.destroy();
});

function openFormulaDialog(): void {
    formulaDialogOpen.value = true;
}

function insertFormula(latex: string, displayMode: boolean): void {
    if (!editor.value) {
        return;
    }

    if (displayMode) {
        editor.value.chain().focus().insertBlockMath({ latex }).run();
        return;
    }

    editor.value.chain().focus().insertInlineMath({ latex }).run();
}

function openImagePicker(): void {
    fileInputRef.value?.click();
}

function onFileSelected(event: Event): void {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];

    if (file && editor.value) {
        insertImageFromFile(editor.value, file);
    }

    input.value = '';
}
</script>

<template>
    <div
        :class="
            cn(
                'overflow-hidden rounded-md border border-input bg-background',
                !compact && 'shadow-xs',
            )
        "
    >
        <div
            v-if="editor && !minimal"
            class="flex flex-wrap items-center gap-1 border-b bg-muted/30 px-2 py-1.5"
        >
            <template v-if="!compact">
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    class="size-8"
                    :class="{ 'bg-muted': editor.isActive('bold') }"
                    @click="editor.chain().focus().toggleBold().run()"
                >
                    <Bold class="size-4" />
                </Button>
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    class="size-8"
                    :class="{ 'bg-muted': editor.isActive('italic') }"
                    @click="editor.chain().focus().toggleItalic().run()"
                >
                    <Italic class="size-4" />
                </Button>
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    class="size-8"
                    :class="{ 'bg-muted': editor.isActive('bulletList') }"
                    @click="editor.chain().focus().toggleBulletList().run()"
                >
                    <List class="size-4" />
                </Button>
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    class="size-8"
                    :class="{ 'bg-muted': editor.isActive('orderedList') }"
                    @click="editor.chain().focus().toggleOrderedList().run()"
                >
                    <ListOrdered class="size-4" />
                </Button>

                <span class="mx-1 h-5 w-px bg-border" />
            </template>

            <Button
                type="button"
                :size="compact ? 'icon' : 'sm'"
                variant="ghost"
                :class="compact ? 'size-8' : 'h-8 gap-1'"
                :disabled="uploading"
                title="Вставить изображение"
                @click="openImagePicker()"
            >
                <ImagePlus class="size-4" />
                <span v-if="!compact">Фото</span>
            </Button>

            <Button
                type="button"
                :size="compact ? 'icon' : 'sm'"
                variant="ghost"
                :class="compact ? 'size-8' : 'h-8 gap-1'"
                title="Вставить формулу"
                @click="openFormulaDialog()"
            >
                <Sigma class="size-4" />
                <span v-if="!compact">Формула</span>
            </Button>

            <template v-if="!compact">
                <span class="mx-1 h-5 w-px bg-border" />

                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    class="size-8"
                    :disabled="!editor.can().chain().focus().undo().run()"
                    @click="editor.chain().focus().undo().run()"
                >
                    <Undo2 class="size-4" />
                </Button>
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    class="size-8"
                    :disabled="!editor.can().chain().focus().redo().run()"
                    @click="editor.chain().focus().redo().run()"
                >
                    <Redo2 class="size-4" />
                </Button>
            </template>
        </div>

        <div v-else-if="editor && minimal" class="flex items-center gap-0.5 border-b px-1 py-0.5">
            <Button
                type="button"
                size="icon"
                variant="ghost"
                class="size-7"
                :disabled="uploading"
                title="Фото (Ctrl+V для скриншота)"
                @click="openImagePicker()"
            >
                <ImagePlus class="size-3.5" />
            </Button>
            <Button
                type="button"
                size="icon"
                variant="ghost"
                class="size-7"
                title="Формула"
                @click="openFormulaDialog()"
            >
                <Sigma class="size-3.5" />
            </Button>
        </div>

        <EditorContent :editor="editor" />

        <input
            ref="fileInputRef"
            type="file"
            accept="image/jpeg,image/png,image/gif,image/webp"
            class="hidden"
            @change="onFileSelected"
        />

        <MathFormulaDialog
            v-model:open="formulaDialogOpen"
            @insert="insertFormula"
        />
    </div>
</template>

<style>
.tiptap-editor p.is-editor-empty:first-child::before {
    color: hsl(var(--muted-foreground));
    content: attr(data-placeholder);
    float: left;
    height: 0;
    pointer-events: none;
}

.tiptap-editor p {
    margin: 0.35rem 0;
}

.tiptap-editor ul,
.tiptap-editor ol {
    margin: 0.35rem 0;
    padding-left: 1.25rem;
}

.tiptap-editor [data-type='block-math'] {
    margin: 0.75rem 0;
}

.tiptap-editor img {
    display: inline-block;
    max-height: 8rem;
    max-width: 100%;
    border-radius: 0.375rem;
    vertical-align: middle;
}
</style>
