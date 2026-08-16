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
Route::middleware(['auth', 'no.cache'])->prefix('zooadmin')->name('admin.')->group(function () {
    Route::get('/', [App\Http\Controllers\AdminController::class, 'index'])->name('index');
    Route::get('dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    Route::post('dashboard/tariffs', [App\Http\Controllers\DashboardController::class, 'updateTariffs'])->name('dashboard.tariffs.update');
    Route::post('tags/classify', App\Http\Controllers\TagClassificationController::class)->name('tags.classify');
    Route::get('settings', [App\Http\Controllers\AdminController::class, 'settings'])->name('settings');
    Route::post('settings/site', [App\Http\Controllers\AdminController::class, 'saveSiteStatus'])->name('settings.site');
    Route::get('settings/telegram-bot', [App\Http\Controllers\TelegramBotSettingsController::class, 'edit'])->name('telegram-bot-settings.edit');
    Route::put('settings/telegram-bot', [App\Http\Controllers\TelegramBotSettingsController::class, 'update'])->name('telegram-bot-settings.update');
    Route::get('personal-data-consent', [App\Http\Controllers\PersonalDataConsentController::class, 'edit'])->name('personal-data-consent.edit');
    Route::post('personal-data-consent', [App\Http\Controllers\PersonalDataConsentController::class, 'update'])->name('personal-data-consent.update');
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
    Route::get('images', [App\Http\Controllers\ImageManagerController::class, 'index'])->name('images.index');
    Route::post('images/refresh', [App\Http\Controllers\ImageManagerController::class, 'refresh'])->name('images.refresh');
    Route::post('images/revert', [App\Http\Controllers\ImageManagerController::class, 'revert'])->name('images.revert');
    Route::resource('socials', App\Http\Controllers\SocialController::class);
    Route::post('socials/status', [App\Http\Controllers\SocialController::class, 'updateStatus'])->name('socials.status');
    Route::resource('clients', App\Http\Controllers\ClientAdminController::class);
    Route::delete('animals/{animal}/photos/{photo}', [App\Http\Controllers\AnimalAdminController::class, 'destroyPhoto'])->name('animals.photos.destroy');
    Route::resource('animals', App\Http\Controllers\AnimalAdminController::class);
    Route::resource('users', App\Http\Controllers\UserController::class);
    Route::resource('feedbacks', FeedbackController::class)->except(['create', 'show']);
    Route::post('feedbacks/reorder', [FeedbackController::class, 'reorder'])->name('feedbacks.reorder');
    Route::resource('categories', App\Http\Controllers\CategoryController::class)->except(['show']);
    Route::get('boarding', [App\Http\Controllers\BoardingController::class, 'index'])->name('boarding.index');
    Route::post('boarding', [App\Http\Controllers\BoardingController::class, 'store'])->name('boarding.store');
    Route::get('boarding/animals', [App\Http\Controllers\BoardingController::class, 'animals'])->name('boarding.animals');
    Route::get('boarding/archive', [App\Http\Controllers\BoardingController::class, 'archiveIndex'])->name('boarding.archive');
    Route::get('boarding/{boarding}/tasks', [App\Http\Controllers\BoardingTaskController::class, 'index'])->name('boarding.tasks.index');
    Route::post('boarding/{boarding}/tasks', [App\Http\Controllers\BoardingTaskController::class, 'store'])->name('boarding.tasks.store');
    Route::put('boarding-tasks/{task}', [App\Http\Controllers\BoardingTaskController::class, 'update'])->name('boarding.tasks.update');
    Route::delete('boarding-tasks/{task}', [App\Http\Controllers\BoardingTaskController::class, 'destroy'])->name('boarding.tasks.destroy');
    Route::put('boarding/{boarding}', [App\Http\Controllers\BoardingController::class, 'update'])->name('boarding.update');
    Route::post('boarding/{boarding}/archive', [App\Http\Controllers\BoardingController::class, 'archive'])->name('boarding.archive.store');
    Route::post('boarding/{boarding}/restore', [App\Http\Controllers\BoardingController::class, 'restore'])->name('boarding.restore');
    Route::delete('boarding/{boarding}', [App\Http\Controllers\BoardingController::class, 'destroy'])->name('boarding.destroy');
    Route::get('boarding/data', [App\Http\Controllers\BoardingController::class, 'data'])->name('boarding.data');
    Route::get('boarding/export', [App\Http\Controllers\BoardingController::class, 'export'])->name('boarding.export');
    Route::resource('articles', App\Http\Controllers\ArticleAdminController::class);
    Route::delete('articles/{article}/images/{image}', [App\Http\Controllers\ArticleAdminController::class, 'destroyImage'])->name('articles.images.destroy');
    Route::post('articles/status', [App\Http\Controllers\ArticleAdminController::class, 'updateStatus'])->name('articles.status');
    Route::resource('article-comments', App\Http\Controllers\ArticleCommentAdminController::class)->only(['index','update','destroy']);
    Route::post('article-comments/status', [App\Http\Controllers\ArticleCommentAdminController::class, 'updateStatus'])->name('article-comments.status');
    Route::post('articles/upload-image', [App\Http\Controllers\ArticleAdminController::class, 'uploadImage'])->name('articles.upload');
    Route::get('avito-reviews', [App\Http\Controllers\AvitoReviewController::class, 'index'])->name('avito-reviews.index');
    Route::get('avito-reviews/create', [App\Http\Controllers\AvitoReviewController::class, 'create'])->name('avito-reviews.create');
    Route::post('avito-reviews', [App\Http\Controllers\AvitoReviewController::class, 'store'])->name('avito-reviews.store');
    Route::post('avito-reviews/sort-by-date', [App\Http\Controllers\AvitoReviewController::class, 'sortByDate'])->name('avito-reviews.sort-by-date');
    Route::get('avito-reviews/{avitoReview}/edit', [App\Http\Controllers\AvitoReviewController::class, 'edit'])->name('avito-reviews.edit');
    Route::put('avito-reviews/{avitoReview}', [App\Http\Controllers\AvitoReviewController::class, 'update'])->name('avito-reviews.update');
    Route::post('avito-reviews/{avitoReview}/status', [App\Http\Controllers\AvitoReviewController::class, 'updateStatus'])->name('avito-reviews.status');
    Route::delete('avito-reviews/{avitoReview}', [App\Http\Controllers\AvitoReviewController::class, 'destroy'])->name('avito-reviews.destroy');
    Route::post('avito-reviews/refresh', [App\Http\Controllers\AvitoReviewController::class, 'refresh'])->name('avito-reviews.refresh');
    Route::post('avito-reviews/import', [App\Http\Controllers\AvitoReviewController::class, 'import'])->name('avito-reviews.import');
    Route::post('avito-reviews/reorder', [App\Http\Controllers\AvitoReviewController::class, 'reorder'])->name('avito-reviews.reorder');
    Route::get('nav-links', [App\Http\Controllers\NavLinkController::class, 'index'])->name('nav-links.index');
    Route::post('nav-links/status', [App\Http\Controllers\NavLinkController::class, 'updateStatus'])->name('nav-links.status');
});

// Main page
Route::get('/', [HomeController::class, 'index']);
Route::get('/v2', [HomeController::class, 'v2'])->name('v2');
Route::get('/calendar', [App\Http\Controllers\BoardingController::class, 'publicCalendar'])->name('calendar.index');
Route::get('/gallery/more', [HomeController::class, 'galleryMore'])->name('gallery.more');
Route::get('/articles', [App\Http\Controllers\ArticlePublicController::class, 'index'])->name('articles.index');
Route::get('/articles/{article:slug}', [App\Http\Controllers\ArticlePublicController::class, 'show'])->name('articles.show');
Route::post('/articles/{article:slug}/comments', [App\Http\Controllers\ArticlePublicController::class, 'comment'])->name('articles.comment');
