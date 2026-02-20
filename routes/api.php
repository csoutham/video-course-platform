<?php

use App\Http\Controllers\Api\V1\Mobile\Auth\LoginController as MobileAuthLoginController;
use App\Http\Controllers\Api\V1\Mobile\Auth\LogoutAllController as MobileAuthLogoutAllController;
use App\Http\Controllers\Api\V1\Mobile\Auth\LogoutController as MobileAuthLogoutController;
use App\Http\Controllers\Api\V1\Mobile\Course\ShowController as MobileCourseShowController;
use App\Http\Controllers\Api\V1\Mobile\LibraryController;
use App\Http\Controllers\Api\V1\Mobile\LessonPlayback\ProgressController as MobileLessonPlaybackProgressController;
use App\Http\Controllers\Api\V1\Mobile\LessonPlayback\ShowController as MobileLessonPlaybackShowController;
use App\Http\Controllers\Api\V1\Mobile\ProfileController;
use App\Http\Controllers\Api\V1\Mobile\ReceiptsController;
use App\Http\Controllers\Api\V1\Mobile\ResourceAccess\FileController as MobileResourceAccessFileController;
use App\Http\Controllers\Api\V1\Mobile\ResourceAccess\ShowController as MobileResourceAccessShowController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/mobile')
    ->middleware('throttle:mobile-api')
    ->name('api.v1.mobile.')
    ->group(function (): void {
        Route::post('/auth/login', MobileAuthLoginController::class)->name('auth.login');

        Route::middleware('auth:sanctum')->group(function (): void {
            Route::post('/auth/logout', MobileAuthLogoutController::class)->name('auth.logout');
            Route::post('/auth/logout-all', MobileAuthLogoutAllController::class)->name('auth.logout-all');

            Route::get('/me', ProfileController::class)->name('me');
            Route::get('/library', LibraryController::class)->name('library');
            Route::get('/receipts', ReceiptsController::class)->name('receipts');
            Route::get('/courses/{courseSlug}', MobileCourseShowController::class)->name('courses.show');
            Route::get('/courses/{courseSlug}/lessons/{lessonSlug}/playback', MobileLessonPlaybackShowController::class)
                ->name('playback.show');
            Route::post('/courses/{courseSlug}/lessons/{lessonSlug}/progress', MobileLessonPlaybackProgressController::class)
                ->name('playback.progress');

            Route::get('/resources/{resource}', MobileResourceAccessShowController::class)->name('resources.show');
        });

        Route::get('/resources/{resource}/file', MobileResourceAccessFileController::class)
            ->middleware('signed')
            ->name('resources.file');
    });
