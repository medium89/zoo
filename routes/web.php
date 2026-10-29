<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Admin\{
    AboutController, AdminController, AdvantageController, AnimalAdminController, ChangeLogController,
    ArticleAdminController, ArticleCommentAdminController, AvitoReviewController, SiteReviewController,
    BoardingController, BoardingTaskController, CategoryController, ClientAdminController,
    ClientMapController, DashboardController, FeedbackAdminController, GalleryController, ImageManagerController,
    NavLinkController, PersonalDataConsentController, ServiceController,
    ServiceOrderAdminController, SliderController, SocialController,
    TagClassificationController, TelegramBotSettingsController, UserController, VisualEditorController,
};
use App\Http\Controllers\Public\{ArticlePublicController, FeedbackController, HomeController, SitemapController, SiteReviewController as PublicSiteReviewController};

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
Route::post('/feedback', [FeedbackController::class, 'store'])->middleware('throttle:10,1')->name('feedback.store');
Route::post('/site-reviews', [PublicSiteReviewController::class, 'store'])->middleware('throttle:3,10')->name('site-reviews.store');

// Admin Routes
Route::middleware(['auth', 'admin', 'no.cache', 'audit.changes'])->prefix('zooadmin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'index'])->name('index');
    Route::get('visual-editor/field', [VisualEditorController::class, 'field'])->name('visual.field');
    Route::patch('visual-editor/field', [VisualEditorController::class, 'saveField'])->name('visual.field.save');
    Route::post('visual-editor/reorder', [VisualEditorController::class, 'reorder'])->name('visual.reorder');
    Route::post('visual-editor/image', [VisualEditorController::class, 'saveImage'])->name('visual.image.save');
    Route::post('visual-editor/nav-link', [VisualEditorController::class, 'createNavLink'])->name('visual.nav-link.create');
    Route::get('logs', [ChangeLogController::class, 'index'])->name('logs.index');
    Route::get('logs/{changeLog}', [ChangeLogController::class, 'show'])->name('logs.show');
    Route::post('logs/{changeLog}/revert', [ChangeLogController::class, 'revert'])->name('logs.revert');
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('dashboard/tariffs', [DashboardController::class, 'updateTariffs'])->name('dashboard.tariffs.update');
    Route::post('tags/classify', TagClassificationController::class)->name('tags.classify');
    Route::get('client-map', [ClientMapController::class, 'index'])->name('client-map.index');
    Route::post('client-map/clients', [ClientMapController::class, 'storeClient'])->name('client-map.clients.store');
    Route::patch('client-map/clients/{client}', [ClientMapController::class, 'updateClient'])->name('client-map.clients.update');
    Route::post('client-map/animals', [ClientMapController::class, 'storeAnimal'])->name('client-map.animals.store');
    Route::patch('client-map/animals/{animal}', [ClientMapController::class, 'updateAnimal'])->name('client-map.animals.update');
    Route::post('client-map/positions', [ClientMapController::class, 'savePositions'])->name('client-map.positions.save');
    Route::post('client-map/animals/{animal}/clients/{client}', [ClientMapController::class, 'attachAnimal'])->name('client-map.animals.attach');
    Route::delete('client-map/animals/{animal}/client', [ClientMapController::class, 'detachAnimal'])->name('client-map.animals.detach');
    Route::get('settings', [AdminController::class, 'settings'])->name('settings');
    Route::post('settings/site', [AdminController::class, 'saveSiteStatus'])->name('settings.site');
    Route::post('settings/seo-files', [AdminController::class, 'saveSeoFiles'])->name('settings.seo-files');
    Route::post('settings/sitemap/rebuild', [AdminController::class, 'rebuildSitemap'])->name('settings.sitemap.rebuild');
    Route::get('settings/telegram-bot', [TelegramBotSettingsController::class, 'edit'])->name('telegram-bot-settings.edit');
    Route::put('settings/telegram-bot', [TelegramBotSettingsController::class, 'update'])->name('telegram-bot-settings.update');
    Route::get('personal-data-consent', [PersonalDataConsentController::class, 'edit'])->name('personal-data-consent.edit');
    Route::post('personal-data-consent', [PersonalDataConsentController::class, 'update'])->name('personal-data-consent.update');
    Route::resource('sliders', SliderController::class);
    Route::post('sliders/status', [SliderController::class, 'updateStatus'])->name('sliders.status');
    Route::post('sliders/hero-mode', [SliderController::class, 'updateHeroMode'])->name('sliders.hero-mode');
    Route::post('sliders/hero-image', [SliderController::class, 'updateHeroImage'])->name('sliders.hero-image');
    Route::get('about', [AboutController::class, 'edit'])->name('about.edit');
    Route::post('about', [AboutController::class, 'update'])->name('about.update');
    Route::resource('advantages', AdvantageController::class);
    Route::post('advantages/status', [AdvantageController::class, 'updateStatus'])->name('advantages.status');
    Route::post('advantages/background', [AdvantageController::class, 'updateBackgroundColor'])->name('advantages.background');
    Route::resource('services', ServiceController::class);
    Route::post('services/status', [ServiceController::class, 'updateStatus'])->name('services.status');
    Route::resource('galleries', GalleryController::class)->except(['show', 'edit', 'update']);
    Route::post('galleries/status', [GalleryController::class, 'updateStatus'])->name('galleries.status');
    Route::get('images', [ImageManagerController::class, 'index'])->name('images.index');
    Route::view('business-cards', 'admin.business-cards.index')->name('business-cards.index');
    Route::post('images/refresh', [ImageManagerController::class, 'refresh'])->name('images.refresh');
    Route::post('images/revert', [ImageManagerController::class, 'revert'])->name('images.revert');
    Route::resource('socials', SocialController::class);
    Route::post('socials/status', [SocialController::class, 'updateStatus'])->name('socials.status');
    Route::post('clients/{client}/animals', [ClientAdminController::class, 'attachAnimal'])->name('clients.animals.attach');
    Route::delete('clients/{client}/animals/{animal}', [ClientAdminController::class, 'detachAnimal'])->name('clients.animals.detach');
    Route::delete('clients/{client}/photos/{photo}', [ClientAdminController::class, 'destroyPhoto'])->name('clients.photos.destroy');
    Route::resource('clients', ClientAdminController::class);
    Route::delete('animals/{animal}/photos/{photo}', [AnimalAdminController::class, 'destroyPhoto'])->name('animals.photos.destroy');
    Route::post('animals/{animal}/client', [AnimalAdminController::class, 'assignClient'])->name('animals.client.assign');
    Route::delete('animals/{animal}/client', [AnimalAdminController::class, 'detachClient'])->name('animals.client.detach');
    Route::resource('animals', AnimalAdminController::class);
    Route::resource('users', UserController::class);
    Route::resource('feedbacks', FeedbackAdminController::class)->only(['index', 'edit', 'update', 'destroy']);
    Route::post('feedbacks/reorder', [FeedbackAdminController::class, 'reorder'])->name('feedbacks.reorder');
    Route::resource('categories', CategoryController::class)->except(['show']);
    Route::get('boarding', [BoardingController::class, 'index'])->name('boarding.index');
    Route::post('boarding', [BoardingController::class, 'store'])->name('boarding.store');
    Route::get('boarding/animals', [BoardingController::class, 'animals'])->name('boarding.animals');
    Route::get('boarding/archive', [BoardingController::class, 'archiveIndex'])->name('boarding.archive');
    Route::get('boarding/{boarding}/tasks', [BoardingTaskController::class, 'index'])->name('boarding.tasks.index');
    Route::post('boarding/{boarding}/tasks', [BoardingTaskController::class, 'store'])->name('boarding.tasks.store');
    Route::put('boarding-tasks/{task}', [BoardingTaskController::class, 'update'])->name('boarding.tasks.update');
    Route::delete('boarding-tasks/{task}', [BoardingTaskController::class, 'destroy'])->name('boarding.tasks.destroy');
    Route::put('boarding/{boarding}', [BoardingController::class, 'update'])->name('boarding.update');
    Route::post('boarding/{boarding}/archive', [BoardingController::class, 'archive'])->name('boarding.archive.store');
    Route::post('boarding/{boarding}/restore', [BoardingController::class, 'restore'])->name('boarding.restore');
    Route::delete('boarding/{boarding}', [BoardingController::class, 'destroy'])->name('boarding.destroy');
    Route::get('boarding/data', [BoardingController::class, 'data'])->name('boarding.data');
    Route::get('boarding/export', [BoardingController::class, 'export'])->name('boarding.export');
    Route::get('service-orders', [ServiceOrderAdminController::class, 'index'])->name('service-orders.index');
    Route::get('service-orders/archive', [ServiceOrderAdminController::class, 'archiveIndex'])->name('service-orders.archive.index');
    Route::post('service-orders', [ServiceOrderAdminController::class, 'store'])->name('service-orders.store');
    Route::post('service-orders/animals/{animal}', [ServiceOrderAdminController::class, 'updateAnimal'])->name('service-orders.animals.update');
    Route::put('service-orders/{serviceOrder}', [ServiceOrderAdminController::class, 'update'])->name('service-orders.update');
    Route::post('service-orders/{serviceOrder}/archive', [ServiceOrderAdminController::class, 'archive'])->name('service-orders.archive');
    Route::post('service-orders/{serviceOrder}/restore', [ServiceOrderAdminController::class, 'restore'])->name('service-orders.restore');
    Route::delete('service-orders/{serviceOrder}', [ServiceOrderAdminController::class, 'destroy'])->name('service-orders.destroy');
    Route::resource('articles', ArticleAdminController::class);
    Route::delete('articles/{article}/images/{image}', [ArticleAdminController::class, 'destroyImage'])->name('articles.images.destroy');
    Route::post('articles/status', [ArticleAdminController::class, 'updateStatus'])->name('articles.status');
    Route::resource('article-comments', ArticleCommentAdminController::class)->only(['index','update','destroy']);
    Route::post('article-comments/status', [ArticleCommentAdminController::class, 'updateStatus'])->name('article-comments.status');
    Route::post('articles/upload-image', [ArticleAdminController::class, 'uploadImage'])->name('articles.upload');
    Route::get('site-reviews', [SiteReviewController::class, 'index'])->name('site-reviews.index');
    Route::patch('site-reviews/{siteReview}/status', [SiteReviewController::class, 'updateStatus'])->name('site-reviews.status');
    Route::delete('site-reviews/{siteReview}', [SiteReviewController::class, 'destroy'])->name('site-reviews.destroy');
    Route::get('avito-reviews', [AvitoReviewController::class, 'index'])->name('avito-reviews.index');
    Route::get('avito-reviews/create', [AvitoReviewController::class, 'create'])->name('avito-reviews.create');
    Route::post('avito-reviews', [AvitoReviewController::class, 'store'])->name('avito-reviews.store');
    Route::post('avito-reviews/sort-by-date', [AvitoReviewController::class, 'sortByDate'])->name('avito-reviews.sort-by-date');
    Route::get('avito-reviews/{avitoReview}/edit', [AvitoReviewController::class, 'edit'])->name('avito-reviews.edit');
    Route::put('avito-reviews/{avitoReview}', [AvitoReviewController::class, 'update'])->name('avito-reviews.update');
    Route::post('avito-reviews/{avitoReview}/status', [AvitoReviewController::class, 'updateStatus'])->name('avito-reviews.status');
    Route::delete('avito-reviews/{avitoReview}', [AvitoReviewController::class, 'destroy'])->name('avito-reviews.destroy');
    Route::post('avito-reviews/refresh', [AvitoReviewController::class, 'refresh'])->name('avito-reviews.refresh');
    Route::post('avito-reviews/import', [AvitoReviewController::class, 'import'])->name('avito-reviews.import');
    Route::post('avito-reviews/reorder', [AvitoReviewController::class, 'reorder'])->name('avito-reviews.reorder');
    Route::get('nav-links', [NavLinkController::class, 'index'])->name('nav-links.index');
    Route::post('nav-links/status', [NavLinkController::class, 'updateStatus'])->name('nav-links.status');
});

// Main page
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/v1', [HomeController::class, 'legacy'])->name('v1');
Route::redirect('/v2', '/', 301)->name('v2');
Route::get('/calendar', [BoardingController::class, 'publicCalendar'])->name('calendar.index');
Route::get('/gallery/more', [HomeController::class, 'galleryMore'])->name('gallery.more');
Route::get('/articles', [ArticlePublicController::class, 'index'])->name('articles.index');
Route::get('/articles/{article:slug}', [ArticlePublicController::class, 'show'])->name('articles.show');
Route::post('/articles/{article:slug}/comments', [ArticlePublicController::class, 'comment'])->middleware('throttle:6,1')->name('articles.comment');
