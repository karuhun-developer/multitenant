<?php

namespace App\Providers;

use App\Models\Menu;
use Illuminate\View\View;
use Illuminate\Support\Facades;
use Illuminate\Support\ServiceProvider;

class ViewServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ...
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Facades\View::composer('components.navigation', function(View $view) {
            $view->with('menus', Menu::orderBy('ordering', 'asc')->with('menuChildren')->where('on', 'cms')->get());
        });
    }
}
