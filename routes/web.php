<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FeedbackController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// Standard authentication routes without registration
Auth::routes(['register' => false]);

// Public Routes
Route::post('/feedback', [FeedbackController::class, 'store'])->name('feedback.store');

// Admin Routes
Route::middleware(['auth'])->prefix('zooadmin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\AdminController::class, 'index'])->name('index');
    Route::get('settings', [App\Http\Controllers\AdminController::class, 'settings'])->name('settings');
    Route::post('settings/site', [App\Http\Controllers\AdminController::class, 'saveSiteStatus'])->name('settings.site');
    Route::resource('sliders', App\Http\Controllers\SliderController::class);
    Route::post('sliders/status', [App\Http\Controllers\SliderController::class, 'updateStatus'])->name('sliders.status');
    Route::get('about', [App\Http\Controllers\AboutController::class, 'edit'])->name('about.edit');
    Route::post('about', [App\Http\Controllers\AboutController::class, 'update'])->name('about.update');
    Route::resource('advantages', App\Http\Controllers\AdvantageController::class);
    Route::post('advantages/status', [App\Http\Controllers\AdvantageController::class, 'updateStatus'])->name('advantages.status');
    Route::resource('services', App\Http\Controllers\ServiceController::class);
    Route::post('services/status', [App\Http\Controllers\ServiceController::class, 'updateStatus'])->name('services.status');
    Route::resource('galleries', App\Http\Controllers\GalleryController::class)->except(['show', 'edit', 'update']);
    Route::post('galleries/status', [App\Http\Controllers\GalleryController::class, 'updateStatus'])->name('galleries.status');
    Route::resource('socials', App\Http\Controllers\SocialController::class);
    Route::post('socials/status', [App\Http\Controllers\SocialController::class, 'updateStatus'])->name('socials.status');
    Route::resource('users', App\Http\Controllers\UserController::class);
    Route::resource('feedbacks', FeedbackController::class)->except(['create', 'show']);
});

// Main page
Route::get('/', [HomeController::class, 'index']);