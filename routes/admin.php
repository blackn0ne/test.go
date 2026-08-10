<?php

use App\Http\Controllers\Admin\DirectoryController;
use App\Http\Controllers\Admin\EditorUploadController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\QuestionController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('users', UserController::class)->except(['show']);

        Route::resource('questions', QuestionController::class)->except(['show']);

        Route::post('editor-uploads', [EditorUploadController::class, 'store'])
            ->name('editor-uploads.store');

        Route::resource('exams', ExamController::class)->only(['index', 'create', 'store']);
        Route::inertia('reports', 'admin/reports/Index')->name('reports.index');

        Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::post('settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::get('directories', [DirectoryController::class, 'index'])->name('directories.index');
        Route::post('directories/classes', [DirectoryController::class, 'storeClass'])->name('directories.classes.store');
        Route::put('directories/classes/{schoolClass}', [DirectoryController::class, 'updateClass'])->name('directories.classes.update');
        Route::delete('directories/classes/{schoolClass}', [DirectoryController::class, 'destroyClass'])->name('directories.classes.destroy');
        Route::post('directories/subjects', [DirectoryController::class, 'storeSubject'])->name('directories.subjects.store');
        Route::put('directories/subjects/{subject}', [DirectoryController::class, 'updateSubject'])->name('directories.subjects.update');
        Route::delete('directories/subjects/{subject}', [DirectoryController::class, 'destroySubject'])->name('directories.subjects.destroy');
        Route::post('directories/groups', [DirectoryController::class, 'storeGroup'])->name('directories.groups.store');
        Route::put('directories/groups/{group}', [DirectoryController::class, 'updateGroup'])->name('directories.groups.update');
        Route::delete('directories/groups/{group}', [DirectoryController::class, 'destroyGroup'])->name('directories.groups.destroy');
    });
