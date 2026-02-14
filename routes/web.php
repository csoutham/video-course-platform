<?php

use App\Http\Controllers\Payments\CheckoutController;
use App\Http\Controllers\Payments\StripeWebhookController;
use App\Livewire\Courses\Catalog;
use App\Livewire\Courses\Detail;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
Route::get('/courses', Catalog::class)->name('courses.index');
Route::get('/courses/{slug}', Detail::class)->name('courses.show');
Route::post('/checkout/{course}', CheckoutController::class)->name('checkout.start');
Route::view('/checkout/success', 'checkout.success')->name('checkout.success');
Route::view('/checkout/cancel', 'checkout.cancel')->name('checkout.cancel');
Route::post('/webhooks/stripe', StripeWebhookController::class)->name('webhooks.stripe');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
