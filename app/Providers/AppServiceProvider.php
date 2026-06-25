<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Attach the sidebar designs composer to the settings page
        \View::composer('admin.placeholders.settings', \App\View\Composers\SettingsSidebarDesignsComposer::class);
    }
}
