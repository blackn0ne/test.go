<?php

use App\Http\Controllers\Auth\AdminAuthenticatedSessionController;
use App\Http\Controllers\ExamAttemptController;
use App\Http\Controllers\UserDirectionController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('admin', [AdminAuthenticatedSessionController::class, 'create'])
        ->name('admin.login');
    Route::post('admin/login', [AdminAuthenticatedSessionController::class, 'store'])
        ->name('admin.login.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::put('direction', [UserDirectionController::class, 'update'])
        ->name('direction.update');

    Route::inertia('dashboard', 'Dashboard')
        ->name('dashboard');

    Route::middleware('direction')->group(function () {
        Route::get('exams/{exam}/take', [ExamAttemptController::class, 'show'])
            ->name('exams.take');
        Route::post('exams/{exam}/start', [ExamAttemptController::class, 'start'])
            ->name('exams.start');
        Route::post('exams/{exam}/submit', [ExamAttemptController::class, 'submit'])
            ->name('exams.submit');
        Route::get('exams/{exam}/attempts/{attempt}', [ExamAttemptController::class, 'result'])
            ->name('exams.result');
    });
});

require __DIR__.'/admin.php';
require __DIR__.'/settings.php';
