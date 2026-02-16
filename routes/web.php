<?php

use App\Http\Controllers\Admin\CoursesController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\CourseLessonsController;
use App\Http\Controllers\Admin\CourseModulesController;
use App\Http\Controllers\Admin\OrdersController;
use App\Http\Controllers\Admin\UdemyImportsController;
use App\Http\Controllers\Admin\UsersController;
use App\Http\Controllers\Learning\CoursePlayerController;
use App\Http\Controllers\Learning\LessonProgressController;
use App\Http\Controllers\Learning\MyCoursesController;
use App\Http\Controllers\Learning\ReceiptsController;
use App\Http\Controllers\Learning\ResourceDownloadController;
use App\Http\Controllers\Gifts\GiftClaimController;
use App\Http\Controllers\Gifts\MyGiftsController;
use App\Http\Controllers\Payments\CheckoutController;
use App\Http\Controllers\Payments\CheckoutSuccessController;
use App\Http\Controllers\Payments\ClaimPurchaseController;
use App\Http\Controllers\Payments\StripeWebhookController;
use App\Livewire\Courses\Catalog;
use App\Livewire\Courses\Detail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (): RedirectResponse => to_route('courses.index'));
Route::get('/courses', Catalog::class)->name('courses.index');
Route::get('/courses/{slug}', Detail::class)->name('courses.show');
Route::post('/checkout/{course}', CheckoutController::class)->name('checkout.start');
Route::get('/checkout/success', CheckoutSuccessController::class)->name('checkout.success');
Route::view('/checkout/cancel', 'checkout.cancel')->name('checkout.cancel');
Route::get('/claim-purchase/{token}', [ClaimPurchaseController::class, 'show'])->name('claim-purchase.show');
Route::post('/claim-purchase/{token}', [ClaimPurchaseController::class, 'store'])->name('claim-purchase.store');
Route::get('/gift-claim/{token}', [GiftClaimController::class, 'show'])->name('gift-claim.show');
Route::post('/gift-claim/{token}', [GiftClaimController::class, 'store'])->name('gift-claim.store');
Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

Route::middleware('auth')->group(function (): void {
    Route::get('/my-courses', MyCoursesController::class)->name('my-courses.index');
    Route::get('/gifts', MyGiftsController::class)->name('gifts.index');
    Route::get('/receipts', [ReceiptsController::class, 'index'])->name('receipts.index');
    Route::get('/receipts/{order:public_id}', [ReceiptsController::class, 'view'])->name('receipts.view');
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

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/courses/create', [CoursesController::class, 'create'])->name('courses.create');
        Route::post('/courses', [CoursesController::class, 'store'])->name('courses.store');
        Route::get('/courses', [CoursesController::class, 'index'])->name('courses.index');
        Route::get('/imports/udemy', [UdemyImportsController::class, 'show'])->name('imports.udemy.show');
        Route::post('/imports/udemy/preview', [UdemyImportsController::class, 'preview'])->name('imports.udemy.preview');
        Route::post('/imports/udemy/commit', [UdemyImportsController::class, 'commit'])->name('imports.udemy.commit');
        Route::get('/courses/{course}/edit', [CoursesController::class, 'edit'])->name('courses.edit');
        Route::put('/courses/{course}', [CoursesController::class, 'update'])->name('courses.update');
        Route::post('/courses/{course}/modules', [CourseModulesController::class, 'store'])->name('modules.store');
        Route::put('/modules/{module}', [CourseModulesController::class, 'update'])->name('modules.update');
        Route::delete('/modules/{module}', [CourseModulesController::class, 'destroy'])->name('modules.destroy');
        Route::post('/modules/{module}/lessons', [CourseLessonsController::class, 'store'])->name('lessons.store');
        Route::put('/lessons/{lesson}', [CourseLessonsController::class, 'update'])->name('lessons.update');
        Route::delete('/lessons/{lesson}', [CourseLessonsController::class, 'destroy'])->name('lessons.destroy');
        Route::get('/orders', [OrdersController::class, 'index'])->name('orders.index');
        Route::get('/users', [UsersController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UsersController::class, 'show'])->name('users.show');
    });

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
