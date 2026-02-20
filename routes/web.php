<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Branding\EditController as AdminBrandingEditController;
use App\Http\Controllers\Admin\Branding\ResetController as AdminBrandingResetController;
use App\Http\Controllers\Admin\Branding\UpdateController as AdminBrandingUpdateController;
use App\Http\Controllers\Admin\CourseLessons\DestroyController as AdminCourseLessonsDestroyController;
use App\Http\Controllers\Admin\CourseLessons\StoreController as AdminCourseLessonsStoreController;
use App\Http\Controllers\Admin\CourseLessons\UpdateController as AdminCourseLessonsUpdateController;
use App\Http\Controllers\Admin\CourseModules\DestroyController as AdminCourseModulesDestroyController;
use App\Http\Controllers\Admin\CourseModules\StoreController as AdminCourseModulesStoreController;
use App\Http\Controllers\Admin\CourseModules\UpdateController as AdminCourseModulesUpdateController;
use App\Http\Controllers\Admin\CourseResources\DestroyController as AdminCourseResourcesDestroyController;
use App\Http\Controllers\Admin\CourseResources\StoreForCourseController as AdminCourseResourcesStoreForCourseController;
use App\Http\Controllers\Admin\CourseResources\StoreForLessonController as AdminCourseResourcesStoreForLessonController;
use App\Http\Controllers\Admin\CourseResources\StoreForModuleController as AdminCourseResourcesStoreForModuleController;
use App\Http\Controllers\Admin\Courses\CreateController as AdminCoursesCreateController;
use App\Http\Controllers\Admin\Courses\EditController as AdminCoursesEditController;
use App\Http\Controllers\Admin\Courses\IndexController as AdminCoursesIndexController;
use App\Http\Controllers\Admin\Courses\StoreController as AdminCoursesStoreController;
use App\Http\Controllers\Admin\Courses\UpdateController as AdminCoursesUpdateController;
use App\Http\Controllers\Admin\Imports\Udemy\CommitController as AdminImportsUdemyCommitController;
use App\Http\Controllers\Admin\Imports\Udemy\PreviewController as AdminImportsUdemyPreviewController;
use App\Http\Controllers\Admin\Imports\Udemy\ShowController as AdminImportsUdemyShowController;
use App\Http\Controllers\Admin\Orders\IndexController as AdminOrdersIndexController;
use App\Http\Controllers\Admin\Users\IndexController as AdminUsersIndexController;
use App\Http\Controllers\Admin\Users\ShowController as AdminUsersShowController;
use App\Http\Controllers\Learning\CoursePlayerController;
use App\Http\Controllers\Learning\LessonProgress\CompleteController as LessonProgressCompleteController;
use App\Http\Controllers\Learning\LessonProgress\VideoController as LessonProgressVideoController;
use App\Http\Controllers\Learning\MyCoursesController;
use App\Http\Controllers\Learning\Receipts\IndexController as ReceiptsIndexController;
use App\Http\Controllers\Learning\Receipts\ViewController as ReceiptsViewController;
use App\Http\Controllers\Learning\ResourceDownload\DownloadController as ResourceDownloadDownloadController;
use App\Http\Controllers\Learning\ResourceDownload\StreamController as ResourceDownloadStreamController;
use App\Http\Controllers\Gifts\GiftClaim\ShowController as GiftClaimShowController;
use App\Http\Controllers\Gifts\GiftClaim\StoreController as GiftClaimStoreController;
use App\Http\Controllers\Gifts\MyGiftsController;
use App\Http\Controllers\Payments\CheckoutController;
use App\Http\Controllers\Payments\CheckoutSuccessController;
use App\Http\Controllers\Payments\ClaimPurchase\ShowController as ClaimPurchaseShowController;
use App\Http\Controllers\Payments\ClaimPurchase\StoreController as ClaimPurchaseStoreController;
use App\Http\Controllers\Payments\StripeWebhookController;
use App\Livewire\Courses\Catalog;
use App\Livewire\Courses\Detail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Route;

Route::get('/', fn (): RedirectResponse => to_route('courses.index'));
Route::get('/courses', Catalog::class)->name('courses.index');
Route::get('/courses/{slug}', Detail::class)->name('courses.show');
Route::post('/checkout/{course}', CheckoutController::class)
    ->middleware('throttle:checkout-start')
    ->name('checkout.start');
Route::get('/checkout/success', CheckoutSuccessController::class)->name('checkout.success');
Route::view('/checkout/cancel', 'checkout.cancel')->name('checkout.cancel');
Route::get('/claim-purchase/{token}', ClaimPurchaseShowController::class)->name('claim-purchase.show');
Route::post('/claim-purchase/{token}', ClaimPurchaseStoreController::class)
    ->middleware('throttle:claim-store')
    ->name('claim-purchase.store');
Route::get('/gift-claim/{token}', GiftClaimShowController::class)->name('gift-claim.show');
Route::post('/gift-claim/{token}', GiftClaimStoreController::class)
    ->middleware('throttle:gift-claim-store')
    ->name('gift-claim.store');
Route::post('/webhooks/stripe', StripeWebhookController::class)
    ->middleware('throttle:stripe-webhook')
    ->name('webhooks.stripe');

Route::middleware('auth')->group(function (): void {
    Route::get('/my-courses', MyCoursesController::class)->name('my-courses.index');
    Route::get('/gifts', MyGiftsController::class)->name('gifts.index');
    Route::get('/receipts', ReceiptsIndexController::class)->name('receipts.index');
    Route::get('/receipts/{order:public_id}', ReceiptsViewController::class)->name('receipts.view');
    Route::get('/learn/{course:slug}/{lessonSlug?}', CoursePlayerController::class)->name('learn.show');
    Route::post('/learn/{course:slug}/{lessonSlug}/progress/complete', LessonProgressCompleteController::class)
        ->name('learn.progress.complete');
    Route::post('/learn/{course:slug}/{lessonSlug}/progress/video', LessonProgressVideoController::class)
        ->name('learn.progress.video');
    Route::get('/resources/{resource}/download', ResourceDownloadDownloadController::class)->name('resources.download');
    Route::get('/resources/{resource}/stream', ResourceDownloadStreamController::class)
        ->middleware('signed')
        ->name('resources.stream');
});

Route::middleware(['auth', 'admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function (): void {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::get('/courses/create', AdminCoursesCreateController::class)->name('courses.create');
        Route::post('/courses', AdminCoursesStoreController::class)->name('courses.store');
        Route::get('/courses', AdminCoursesIndexController::class)->name('courses.index');
        Route::get('/imports/udemy', AdminImportsUdemyShowController::class)->name('imports.udemy.show');
        Route::post('/imports/udemy/preview', AdminImportsUdemyPreviewController::class)->name('imports.udemy.preview');
        Route::post('/imports/udemy/commit', AdminImportsUdemyCommitController::class)->name('imports.udemy.commit');
        Route::get('/courses/{course}/edit', AdminCoursesEditController::class)->name('courses.edit');
        Route::put('/courses/{course}', AdminCoursesUpdateController::class)->name('courses.update');
        Route::post('/courses/{course}/modules', AdminCourseModulesStoreController::class)->name('modules.store');
        Route::put('/modules/{module}', AdminCourseModulesUpdateController::class)->name('modules.update');
        Route::delete('/modules/{module}', AdminCourseModulesDestroyController::class)->name('modules.destroy');
        Route::post('/modules/{module}/lessons', AdminCourseLessonsStoreController::class)->name('lessons.store');
        Route::put('/lessons/{lesson}', AdminCourseLessonsUpdateController::class)->name('lessons.update');
        Route::delete('/lessons/{lesson}', AdminCourseLessonsDestroyController::class)->name('lessons.destroy');
        Route::post('/courses/{course}/resources', AdminCourseResourcesStoreForCourseController::class)->name('resources.course.store');
        Route::post('/modules/{module}/resources', AdminCourseResourcesStoreForModuleController::class)->name('resources.module.store');
        Route::post('/lessons/{lesson}/resources', AdminCourseResourcesStoreForLessonController::class)->name('resources.lesson.store');
        Route::delete('/resources/{resource}', AdminCourseResourcesDestroyController::class)->name('resources.destroy');
        Route::get('/orders', AdminOrdersIndexController::class)->name('orders.index');
        Route::get('/users', AdminUsersIndexController::class)->name('users.index');
        Route::get('/users/{user}', AdminUsersShowController::class)->name('users.show');
        Route::get('/branding', AdminBrandingEditController::class)->name('branding.edit');
        Route::put('/branding', AdminBrandingUpdateController::class)->name('branding.update');
        Route::post('/branding/reset', AdminBrandingResetController::class)->name('branding.reset');
    });

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
