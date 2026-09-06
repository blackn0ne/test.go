<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('direction_subject', function (Blueprint $table) {
            $table->foreignId('direction_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('position');
            $table->primary(['direction_id', 'position']);
            $table->unique(['direction_id', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direction_subject');
        Schema::dropIfExists('directions');
    }
};
