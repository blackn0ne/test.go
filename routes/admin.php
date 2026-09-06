<?php

use App\Http\Controllers\Admin\DirectoryController;
use App\Http\Controllers\Admin\EditorUploadController;
use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\PromoCodeController;
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

        Route::resource('exams', ExamController::class)->except(['show']);
        Route::get('promo-codes', [PromoCodeController::class, 'index'])->name('promo-codes.index');
        Route::get('promo-codes/create', [PromoCodeController::class, 'create'])->name('promo-codes.create');
        Route::post('promo-codes', [PromoCodeController::class, 'store'])->name('promo-codes.store');
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
        Route::post('directories/directions', [DirectoryController::class, 'storeDirection'])->name('directories.directions.store');
        Route::put('directories/directions/{direction}', [DirectoryController::class, 'updateDirection'])->name('directories.directions.update');
        Route::delete('directories/directions/{direction}', [DirectoryController::class, 'destroyDirection'])->name('directories.directions.destroy');
        Route::post('directories/groups', [DirectoryController::class, 'storeGroup'])->name('directories.groups.store');
        Route::put('directories/groups/{group}', [DirectoryController::class, 'updateGroup'])->name('directories.groups.update');
        Route::delete('directories/groups/{group}', [DirectoryController::class, 'destroyGroup'])->name('directories.groups.destroy');
        Route::post('directories/regions', [DirectoryController::class, 'storeRegion'])->name('directories.regions.store');
        Route::put('directories/regions/{region}', [DirectoryController::class, 'updateRegion'])->name('directories.regions.update');
        Route::delete('directories/regions/{region}', [DirectoryController::class, 'destroyRegion'])->name('directories.regions.destroy');
        Route::post('directories/districts', [DirectoryController::class, 'storeDistrict'])->name('directories.districts.store');
        Route::put('directories/districts/{district}', [DirectoryController::class, 'updateDistrict'])->name('directories.districts.update');
        Route::delete('directories/districts/{district}', [DirectoryController::class, 'destroyDistrict'])->name('directories.districts.destroy');
    });
