<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('in_progress');
            $table->timestamp('started_at');
            $table->timestamp('submitted_at')->nullable();
            $table->decimal('total_score', 8, 2)->nullable();
            $table->decimal('max_score', 8, 2)->nullable();
            $table->timestamps();

            $table->unique(['exam_id', 'user_id'], 'exam_attempts_exam_user_unique');
            $table->index(['user_id', 'status'], 'exam_attempts_user_status_index');
            $table->index(['exam_id', 'status'], 'exam_attempts_exam_status_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_attempts');
    }
};
