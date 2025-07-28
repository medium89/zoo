<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

// Стандартные маршруты авторизации
Auth::routes();

// Группа маршрутов для админки
Route::middleware(['auth'])->prefix('zooadmin')->group(function () {
    Route::get('/', [App\Http\Controllers\AdminController::class, 'index'])->name('admin.index');
    Route::resource('sliders', App\Http\Controllers\SliderController::class);
    Route::get('about', [App\Http\Controllers\AboutController::class, 'edit'])->name('about.edit');
    Route::post('about', [App\Http\Controllers\AboutController::class, 'update'])->name('about.update');
    Route::resource('advantages', App\Http\Controllers\AdvantageController::class);
    Route::resource('services', App\Http\Controllers\ServiceController::class);
    Route::resource('galleries', App\Http\Controllers\GalleryController::class)->except(['show', 'edit', 'update']);
    Route::resource('socials', App\Http\Controllers\SocialController::class);
    Route::resource('users', App\Http\Controllers\UserController::class);
});

// Главная страница
Route::get('/', [App\Http\Controllers\HomeController::class, 'index']); 