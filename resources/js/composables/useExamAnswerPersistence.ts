import { ref, watch, type Ref } from 'vue';

type AnswerPayload = {
    exam_attempt_question_id: number;
    selected_option_ids: number[];
};

function csrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export function buildSavePayloadFromSelections(
    selections: Record<number, number[]>,
): AnswerPayload[] {
    return Object.entries(selections).map(([questionId, optionIds]) => ({
        exam_attempt_question_id: Number(questionId),
        selected_option_ids: optionIds,
    }));
}

export function useExamAnswerPersistence(
    examId: number,
    selections: Ref<Record<number, number[]>>,
    buildPayload: () => AnswerPayload[],
): { isSaving: Ref<boolean> } {
    const isSaving = ref(false);
    let timer: ReturnType<typeof setTimeout> | null = null;

    async function persist(): Promise<void> {
        const payload = buildPayload();

        if (payload.length === 0) {
            return;
        }

        isSaving.value = true;

        try {
            const response = await fetch(`/exams/${examId}/answers`, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': csrfToken(),
                },
                body: JSON.stringify({ answers: payload }),
            });

            if (! response.ok) {
                throw new Error(`Save failed: ${response.status}`);
            }
        } finally {
            isSaving.value = false;
        }
    }

    watch(
        selections,
        () => {
            if (timer !== null) {
                clearTimeout(timer);
            }

            timer = setTimeout(() => {
                void persist();
            }, 350);
        },
        { deep: true },
    );

    return { isSaving };
}

export function buildSelectionsFromSavedAnswers(
    savedAnswers: AnswerPayload[],
): Record<number, number[]> {
    return savedAnswers.reduce<Record<number, number[]>>((carry, answer) => {
        if (answer.selected_option_ids.length > 0) {
            carry[answer.exam_attempt_question_id] = answer.selected_option_ids;
        }

        return carry;
    }, {});
}
