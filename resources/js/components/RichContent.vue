<script setup lang="ts">
import katex from 'katex';
import { nextTick, onMounted, ref, watch } from 'vue';
import { cn } from '@/lib/utils';
import 'katex/dist/katex.min.css';

const props = withDefaults(
    defineProps<{
        content?: string;
        tag?: keyof HTMLElementTagNameMap;
        compact?: boolean;
    }>(),
    {
        content: '',
        tag: 'div',
        compact: false,
    },
);

const root = ref<HTMLElement | null>(null);

function renderMath(): void {
    if (!root.value) {
        return;
    }

    root.value
        .querySelectorAll<HTMLElement>('[data-type="inline-math"]')
        .forEach((element) => {
            const latex = element.getAttribute('data-latex') ?? '';

            try {
                katex.render(latex, element, {
                    throwOnError: false,
                    displayMode: false,
                });
            } catch {
                element.textContent = latex;
            }
        });

    root.value
        .querySelectorAll<HTMLElement>('[data-type="block-math"]')
        .forEach((element) => {
            const latex = element.getAttribute('data-latex') ?? '';

            try {
                katex.render(latex, element, {
                    throwOnError: false,
                    displayMode: true,
                });
            } catch {
                element.textContent = latex;
            }
        });

    root.value.querySelectorAll<HTMLElement>('.math-tex').forEach((element) => {
        const latex =
            element.getAttribute('data-latex') ??
            element.textContent ??
            '';

        if (!latex) {
            return;
        }

        try {
            katex.render(latex, element, {
                throwOnError: false,
                displayMode: false,
            });
        } catch {
            element.textContent = latex;
        }
    });
}

onMounted(async () => {
    await nextTick();
    renderMath();
});

watch(
    () => props.content,
    async () => {
        await nextTick();
        renderMath();
    },
);
</script>

<template>
    <component
        :is="tag"
        ref="root"
        :class="
            cn(
                'rich-content prose max-w-none dark:prose-invert',
                compact ? 'rich-content-compact prose-sm' : 'prose-sm',
            )
        "
        v-html="content"
    />
</template>

<style>
.rich-content .katex {
    font-size: 1.05em;
}

.rich-content [data-type='block-math'] {
    display: block;
    margin: 0.75rem 0;
    overflow-x: auto;
}

.rich-content-compact .katex {
    font-size: 0.92em;
}

.rich-content-compact p,
.rich-content-compact ul,
.rich-content-compact ol {
    margin: 0;
}

.rich-content-compact [data-type='block-math'] {
    margin: 0.25rem 0;
}

.rich-content img {
    display: inline-block;
    max-width: 100%;
    height: auto;
    border-radius: 0.375rem;
    vertical-align: middle;
}

.rich-content-compact img {
    max-height: 4rem;
}
</style>
