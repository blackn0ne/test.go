import { useHttp } from '@inertiajs/vue3';
import { ref, watch, type Ref } from 'vue';
import ExamAttemptController from '@/actions/App/Http/Controllers/ExamAttemptController';

type AnswerPayload = {
    exam_attempt_question_id: number;
    selected_option_ids: number[];
};

export function useExamAnswerPersistence(
    examId: number,
    selections: Ref<Record<number, number[]>>,
    buildPayload: () => AnswerPayload[],
): { isSaving: Ref<boolean> } {
    const http = useHttp();
    const isSaving = ref(false);
    let timer: ReturnType<typeof setTimeout> | null = null;

    async function persist(): Promise<void> {
        isSaving.value = true;

        try {
            const saveUrl =
                typeof ExamAttemptController.saveAnswers?.url === 'function'
                    ? ExamAttemptController.saveAnswers.url(examId)
                    : `/exams/${examId}/answers`;

            await http.post(saveUrl, {
                data: {
                    answers: buildPayload(),
                },
            });
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
