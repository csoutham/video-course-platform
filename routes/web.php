<?php

use App\Http\Controllers\Learning\CoursePlayerController;
use App\Http\Controllers\Learning\LessonProgressController;
use App\Http\Controllers\Learning\MyCoursesController;
use App\Http\Controllers\Learning\ResourceDownloadController;
use App\Http\Controllers\Payments\CheckoutController;
use App\Http\Controllers\Payments\CheckoutSuccessController;
use App\Http\Controllers\Payments\ClaimPurchaseController;
use App\Http\Controllers\Payments\StripeWebhookController;
use App\Livewire\Courses\Catalog;
use App\Livewire\Courses\Detail;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
Route::get('/courses', Catalog::class)->name('courses.index');
Route::get('/courses/{slug}', Detail::class)->name('courses.show');
Route::post('/checkout/{course}', CheckoutController::class)->name('checkout.start');
Route::get('/checkout/success', CheckoutSuccessController::class)->name('checkout.success');
Route::view('/checkout/cancel', 'checkout.cancel')->name('checkout.cancel');
Route::get('/claim-purchase/{token}', [ClaimPurchaseController::class, 'show'])->name('claim-purchase.show');
Route::post('/claim-purchase/{token}', [ClaimPurchaseController::class, 'store'])->name('claim-purchase.store');
Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

Route::middleware('auth')->group(function (): void {
    Route::get('/my-courses', MyCoursesController::class)->name('my-courses.index');
    Route::get('/learn/{course:slug}/{lessonSlug?}', CoursePlayerController::class)->name('learn.show');
    Route::post('/learn/{course:slug}/{lessonSlug}/progress/complete', [LessonProgressController::class, 'complete'])
        ->name('learn.progress.complete');
    Route::post('/learn/{course:slug}/{lessonSlug}/progress/video', [LessonProgressController::class, 'video'])
        ->name('learn.progress.video');
    Route::get('/resources/{resource}/download', [ResourceDownloadController::class, 'download'])->name('resources.download');
    Route::get('/resources/{resource}/stream', [ResourceDownloadController::class, 'stream'])
        ->middleware('signed')
        ->name('resources.stream');
});

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
