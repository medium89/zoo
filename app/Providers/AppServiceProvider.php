<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Schema;
use App\Models\NavLink;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            return;
        }

        try {
            if (! Schema::hasTable('nav_links')) {
                return;
            }
        } catch (Throwable) {
            return;
        }

        View::composer('sections.header', function ($view) {
            $links = NavLink::orderBy('order')->get();
            $view->with('navLinks', $links);
        });
    }
}
