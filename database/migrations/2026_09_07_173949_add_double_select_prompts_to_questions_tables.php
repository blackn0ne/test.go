<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('questions', function (Blueprint $table) {
            $table->longText('double_first_prompt')->nullable()->after('body');
            $table->longText('double_second_prompt')->nullable()->after('double_first_prompt');
        });

        Schema::table('exam_attempt_questions', function (Blueprint $table) {
            $table->longText('double_first_prompt')->nullable()->after('body');
            $table->longText('double_second_prompt')->nullable()->after('double_first_prompt');
        });
    }

    public function down(): void
    {
        Schema::table('exam_attempt_questions', function (Blueprint $table) {
            $table->dropColumn(['double_first_prompt', 'double_second_prompt']);
        });

        Schema::table('questions', function (Blueprint $table) {
            $table->dropColumn(['double_first_prompt', 'double_second_prompt']);
        });
    }
};
