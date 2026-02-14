<?php

use App\Livewire\Courses\Catalog;
use App\Livewire\Courses\Detail;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');
Route::get('/courses', Catalog::class)->name('courses.index');
Route::get('/courses/{slug}', Detail::class)->name('courses.show');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
