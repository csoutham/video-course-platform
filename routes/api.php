<?php

use App\Http\Controllers\Api\V1\Mobile\AuthController;
use App\Http\Controllers\Api\V1\Mobile\CourseController;
use App\Http\Controllers\Api\V1\Mobile\LibraryController;
use App\Http\Controllers\Api\V1\Mobile\LessonPlaybackController;
use App\Http\Controllers\Api\V1\Mobile\ProfileController;
use App\Http\Controllers\Api\V1\Mobile\ReceiptsController;
use App\Http\Controllers\Api\V1\Mobile\ResourceAccessController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/mobile')
    ->middleware('throttle:mobile-api')
    ->name('api.v1.mobile.')
    ->group(function (): void {
        Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
            Route::post('/auth/logout-all', [AuthController::class, 'logoutAll'])->name('auth.logout-all');

            Route::get('/me', ProfileController::class)->name('me');
            Route::get('/library', LibraryController::class)->name('library');
            Route::get('/receipts', ReceiptsController::class)->name('receipts');
            Route::get('/courses/{courseSlug}', [CourseController::class, 'show'])->name('courses.show');
            Route::get('/courses/{courseSlug}/lessons/{lessonSlug}/playback', [LessonPlaybackController::class, 'show'])
                ->name('playback.show');
            Route::post('/courses/{courseSlug}/lessons/{lessonSlug}/progress', [LessonPlaybackController::class, 'progress'])
                ->name('playback.progress');

            Route::get('/resources/{resource}', [ResourceAccessController::class, 'show'])->name('resources.show');
        });

        Route::get('/resources/{resource}/file', [ResourceAccessController::class, 'file'])
            ->middleware('signed')
            ->name('resources.file');
    });
