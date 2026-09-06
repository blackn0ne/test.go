export type ExamSection = {
    order: number;
    name: string;
    subject_id: number | null;
    kind: string;
    question_count?: number;
};

export type ExamToolId = 'calculator' | 'periodic-table' | 'instructions';

export type ExamInfo = {
    id: number;
    title: string;
    description: string | null;
    duration_minutes: number | null;
    starts_at: string | null;
    ends_at: string | null;
    period_label: string | null;
};

export type AttemptInfo = {
    id: number;
    status: string;
    started_at: string;
    max_score: number | null;
};

export type QuestionOption = {
    id: number;
    question_option_id: number;
    label: string;
    content: string;
    select_group: string | null;
    sort_order: number;
};

export type ExamQuestion = {
    id: number;
    question_id: number;
    type: 'single' | 'multiple' | 'double';
    body: string;
    sort_order: number;
    subject_id: number | null;
    subject_name: string | null;
    section_order: number | null;
    context_id: number | null;
    context_title: string | null;
    context_body: string | null;
    options: QuestionOption[];
};
