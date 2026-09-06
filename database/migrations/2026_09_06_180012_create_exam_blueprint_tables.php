<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_blueprints', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->unsignedSmallInteger('total_questions');
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('exam_blueprint_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_blueprint_id')->constrained()->cascadeOnDelete();
            $table->string('section_kind');
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedTinyInteger('profile_position')->nullable();
            $table->unsignedSmallInteger('question_count');
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();

            $table->unique(['exam_blueprint_id', 'sort_order']);
        });

        Schema::create('exam_blueprint_slot_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_blueprint_section_id')
                ->constrained('exam_blueprint_sections')
                ->cascadeOnDelete();
            $table->unsignedTinyInteger('slot_from');
            $table->unsignedTinyInteger('slot_to');
            $table->string('question_type');
            $table->boolean('requires_context')->default(false);
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_blueprint_slot_rules');
        Schema::dropIfExists('exam_blueprint_sections');
        Schema::dropIfExists('exam_blueprints');
    }
};
