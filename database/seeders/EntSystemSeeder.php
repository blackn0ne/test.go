<?php

namespace Database\Seeders;

use App\Enums\BlueprintSectionKind;
use App\Enums\QuestionType;
use App\Models\ExamBlueprint;
use App\Models\ExamBlueprintSection;
use App\Models\ExamBlueprintSlotRule;
use App\Support\CoreSubjects;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class EntSystemSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Только шаблон ЕНТ. Обязательные предметы должны уже существовать в справочнике.
     * Запуск: php artisan db:seed --class=EntSystemSeeder
     */
    public function run(): void
    {
        $reading = CoreSubjects::resolve('reading_literacy');
        $mathLiteracy = CoreSubjects::resolve('math_literacy');
        $history = CoreSubjects::resolve('kazakhstan_history');

        $blueprint = ExamBlueprint::query()->updateOrCreate(
            ['code' => 'ent_standard'],
            [
                'name' => 'ЕНТ стандарт (120 вопросов)',
                'total_questions' => 120,
                'is_default' => true,
            ],
        );

        $blueprint->sections()->delete();

        $this->seedCoreSection($blueprint->id, $reading->id, 10, 1);
        $this->seedCoreSection($blueprint->id, $mathLiteracy->id, 10, 2);
        $this->seedCoreSection($blueprint->id, $history->id, 20, 3);
        $this->seedProfileSection($blueprint->id, 1, 40, 4);
        $this->seedProfileSection($blueprint->id, 2, 40, 5);
    }

    private function seedCoreSection(int $blueprintId, int $subjectId, int $count, int $sortOrder): void
    {
        $section = ExamBlueprintSection::query()->create([
            'exam_blueprint_id' => $blueprintId,
            'section_kind' => BlueprintSectionKind::Core,
            'subject_id' => $subjectId,
            'profile_position' => null,
            'question_count' => $count,
            'sort_order' => $sortOrder,
        ]);

        ExamBlueprintSlotRule::query()->create([
            'exam_blueprint_section_id' => $section->id,
            'slot_from' => 1,
            'slot_to' => $count,
            'question_type' => QuestionType::Single,
            'requires_context' => false,
            'sort_order' => 1,
        ]);
    }

    private function seedProfileSection(int $blueprintId, int $profilePosition, int $count, int $sortOrder): void
    {
        $section = ExamBlueprintSection::query()->create([
            'exam_blueprint_id' => $blueprintId,
            'section_kind' => BlueprintSectionKind::Profile,
            'subject_id' => null,
            'profile_position' => $profilePosition,
            'question_count' => $count,
            'sort_order' => $sortOrder,
        ]);

        $rules = [
            [1, 25, QuestionType::Single, false],
            [26, 30, QuestionType::Single, true],
            [31, 35, QuestionType::Double, false],
            [36, 40, QuestionType::Multiple, false],
        ];

        foreach ($rules as $index => [$from, $to, $type, $requiresContext]) {
            ExamBlueprintSlotRule::query()->create([
                'exam_blueprint_section_id' => $section->id,
                'slot_from' => $from,
                'slot_to' => $to,
                'question_type' => $type,
                'requires_context' => $requiresContext,
                'sort_order' => $index + 1,
            ]);
        }
    }
}
