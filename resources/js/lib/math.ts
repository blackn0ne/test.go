export function escapeHtml(value: string): string {
    return value
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

export function escapeAttr(value: string): string {
    return value.replace(/"/g, '&quot;');
}

/** Convert stored HTML (Tiptap / legacy) to plain text with $...$ markers. */
export function htmlToPlainMathText(html: string): string {
    if (!html.trim()) {
        return '';
    }

    const container = document.createElement('div');
    container.innerHTML = html;

    container.querySelectorAll('[data-type="inline-math"]').forEach((element) => {
        const latex = element.getAttribute('data-latex') ?? '';
        element.replaceWith(document.createTextNode(`$${latex}$`));
    });

    container.querySelectorAll('[data-type="block-math"]').forEach((element) => {
        const latex = element.getAttribute('data-latex') ?? '';
        element.replaceWith(document.createTextNode(`$$${latex}$$`));
    });

    container.querySelectorAll('.math-tex').forEach((element) => {
        const latex =
            element.getAttribute('data-latex') ??
            element.textContent ??
            '';
        element.replaceWith(document.createTextNode(`$${latex}$`));
    });

    return (container.textContent ?? '').trim();
}

/** Convert plain text with $...$ / $$...$$ to HTML for storage. */
export function plainMathTextToHtml(text: string): string {
    const trimmed = text.trim();

    if (!trimmed) {
        return '';
    }

    if (trimmed.startsWith('<')) {
        return trimmed;
    }

    let html = escapeHtml(trimmed);

    html = html.replace(/\$\$([^$]+)\$\$/g, (_, latex: string) =>
        `<span data-type="block-math" data-latex="${escapeAttr(latex.trim())}"></span>`,
    );

    html = html.replace(/\$([^$]+)\$/g, (_, latex: string) =>
        `<span data-type="inline-math" data-latex="${escapeAttr(latex.trim())}"></span>`,
    );

    return `<p>${html.replace(/\n/g, '<br>')}</p>`;
}

export function insertAtCursor(
    value: string,
    insertion: string,
    selectionStart: number,
    selectionEnd: number,
): { nextValue: string; nextCursor: number } {
    const nextValue =
        value.slice(0, selectionStart) +
        insertion +
        value.slice(selectionEnd);

    return {
        nextValue,
        nextCursor: selectionStart + insertion.length,
    };
}
