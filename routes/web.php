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
    Route::resource('animals', App\Http\Controllers\AnimalAdminController::class)->except(['show']);
    Route::resource('users', App\Http\Controllers\UserController::class);
    Route::resource('feedbacks', FeedbackController::class)->except(['create', 'show']);
    Route::get('boarding', [App\Http\Controllers\BoardingController::class, 'index'])->name('boarding.index');
    Route::post('boarding', [App\Http\Controllers\BoardingController::class, 'store'])->name('boarding.store');
    Route::get('boarding/animals', [App\Http\Controllers\BoardingController::class, 'animals'])->name('boarding.animals');
    Route::get('boarding/archive', [App\Http\Controllers\BoardingController::class, 'archiveIndex'])->name('boarding.archive');
    Route::put('boarding/{boarding}', [App\Http\Controllers\BoardingController::class, 'update'])->name('boarding.update');
    Route::post('boarding/{boarding}/archive', [App\Http\Controllers\BoardingController::class, 'archive'])->name('boarding.archive.store');
    Route::post('boarding/{boarding}/restore', [App\Http\Controllers\BoardingController::class, 'restore'])->name('boarding.restore');
    Route::delete('boarding/{boarding}', [App\Http\Controllers\BoardingController::class, 'destroy'])->name('boarding.destroy');
    Route::get('boarding/data', [App\Http\Controllers\BoardingController::class, 'data'])->name('boarding.data');
    Route::get('boarding/export', [App\Http\Controllers\BoardingController::class, 'export'])->name('boarding.export');
    Route::resource('articles', App\Http\Controllers\ArticleAdminController::class);
    Route::resource('article-comments', App\Http\Controllers\ArticleCommentAdminController::class)->only(['index','update','destroy']);
    Route::post('articles/upload-image', [App\Http\Controllers\ArticleAdminController::class, 'uploadImage'])->name('articles.upload');
});

// Main page
Route::get('/', [HomeController::class, 'index']);
Route::get('/gallery/more', [HomeController::class, 'galleryMore'])->name('gallery.more');
Route::get('/articles', [App\Http\Controllers\ArticlePublicController::class, 'index'])->name('articles.index');
Route::get('/articles/{article}', [App\Http\Controllers\ArticlePublicController::class, 'show'])->name('articles.show');
Route::post('/articles/{article}/comments', [App\Http\Controllers\ArticlePublicController::class, 'comment'])->name('articles.comment');
