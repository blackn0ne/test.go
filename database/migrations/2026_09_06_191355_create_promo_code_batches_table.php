<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promo_code_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedSmallInteger('coupons_per_student');
            $table->unsignedInteger('students_count');
            $table->unsignedInteger('total_codes');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index(['school_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promo_code_batches');
    }
};
