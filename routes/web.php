<?php

use App\Http\Controllers\ExamAttemptController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::get('exams/{exam}/take', [ExamAttemptController::class, 'show'])
        ->name('exams.take');
    Route::post('exams/{exam}/submit', [ExamAttemptController::class, 'submit'])
        ->name('exams.submit');
    Route::get('exams/{exam}/attempts/{attempt}', [ExamAttemptController::class, 'result'])
        ->name('exams.result');
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
