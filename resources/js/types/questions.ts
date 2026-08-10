export type QuestionTypeValue = 'single' | 'multiple' | 'double';

export type QuestionContextMode = 'none' | 'existing' | 'new';

export type OptionLabelSingle = 'A' | 'B' | 'C' | 'D';
export type OptionLabelMultiple = 'A' | 'B' | 'C' | 'D' | 'E' | 'F';
export type OptionLabel = OptionLabelSingle | OptionLabelMultiple;

export type QuestionOptionForm = {
    label: OptionLabel;
    content: string;
    is_correct: boolean;
    select_group: 'first' | 'second' | null;
    sort_order: number;
};

export type QuestionFormData = {
    subject_id: number | '';
    type: QuestionTypeValue;
    body: string;
    context_mode: QuestionContextMode;
    context_id: number | '';
    context_title: string;
    context_body: string;
    options: QuestionOptionForm[];
};

export type SubjectOption = {
    id: number;
    name: string;
};

export type QuestionTypeOption = {
    value: QuestionTypeValue;
    label: string;
};

export type QuestionContextOption = {
    id: number;
    subject_id: number;
    title: string | null;
    label: string;
};

const singleLabels = ['A', 'B', 'C', 'D'] as const;
const multipleLabels = ['A', 'B', 'C', 'D', 'E', 'F'] as const;

export function buildDefaultOptions(
    type: QuestionTypeValue,
): QuestionOptionForm[] {
    if (type === 'double') {
        return [
            ...singleLabels.map((label, index) => ({
                label,
                content: '',
                is_correct: false,
                select_group: 'first' as const,
                sort_order: index,
            })),
            ...singleLabels.map((label, index) => ({
                label,
                content: '',
                is_correct: false,
                select_group: 'second' as const,
                sort_order: index + 4,
            })),
        ];
    }

    if (type === 'multiple') {
        return multipleLabels.map((label, index) => ({
            label,
            content: '',
            is_correct: false,
            select_group: null,
            sort_order: index,
        }));
    }

    return singleLabels.map((label, index) => ({
        label,
        content: '',
        is_correct: false,
        select_group: null,
        sort_order: index,
    }));
}

export function emptyQuestionForm(): QuestionFormData {
    return {
        subject_id: '',
        type: 'single',
        body: '',
        context_mode: 'none',
        context_id: '',
        context_title: '',
        context_body: '',
        options: buildDefaultOptions('single'),
    };
}

function hasMeaningfulHtml(value: string): boolean {
    if (typeof document === 'undefined') {
        const text = value.replace(/<[^>]*>/g, '').trim();

        return text !== '' || /<img[\s>]/i.test(value);
    }

    const element = document.createElement('div');
    element.innerHTML = value;

    if ((element.textContent ?? '').trim() !== '') {
        return true;
    }

    return element.querySelector('img') !== null;
}

export function validateQuestionForm(
    form: QuestionFormData,
): Record<string, string> {
    const errors: Record<string, string> = {};

    if (!form.subject_id) {
        errors.subject_id = 'Выберите предмет.';
    }

    if (!hasMeaningfulHtml(form.body)) {
        errors.body = 'Введите условие вопроса.';
    }

    if (form.context_mode === 'existing' && !form.context_id) {
        errors.context_id = 'Выберите контекст.';
    }

    if (form.context_mode === 'new' && !hasMeaningfulHtml(form.context_body)) {
        errors.context_body = 'Заполните текст контекста.';
    }

    const correctCount = form.options.filter((option) => option.is_correct).length;

    if (form.type === 'single' && correctCount !== 1) {
        errors.options = 'Отметьте один правильный вариант ответа.';
    }

    if (form.type === 'multiple' && correctCount < 1) {
        errors.options = 'Отметьте хотя бы один правильный вариант.';
    }

    if (form.type === 'double') {
        for (const group of ['first', 'second'] as const) {
            const groupCorrect = form.options.filter(
                (option) => option.select_group === group && option.is_correct,
            ).length;

            if (groupCorrect !== 1) {
                errors.options = `В ${group === 'first' ? 'первом' : 'втором'} селекте выберите один правильный ответ.`;
                break;
            }
        }
    }

    for (const option of form.options) {
        if (!hasMeaningfulHtml(option.content)) {
            errors[`options.${option.sort_order}.content`] =
                `Заполните текст варианта ${option.label}.`;
        }
    }

    return errors;
}

export function questionFormFromQuestion(question: {
    subject_id: number;
    type: QuestionTypeValue;
    body: string;
    options: QuestionOptionForm[];
    context?: {
        id: number;
        title: string | null;
        body: string;
    } | null;
}): QuestionFormData {
    return {
        subject_id: question.subject_id,
        type: question.type,
        body: question.body,
        context_mode: question.context ? 'existing' : 'none',
        context_id: question.context?.id ?? '',
        context_title: question.context?.title ?? '',
        context_body: question.context?.body ?? '',
        options: question.options,
    };
}
