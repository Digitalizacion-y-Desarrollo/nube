<?php

namespace App\Providers;

use App\Models\File;
use App\Observers\FileObserver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as ViewContract;

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
        File::observe(FileObserver::class);

        View::composer('components.navigation.header', function (ViewContract $view): void {
            $user = Auth::user();

            $view->with([
                'notifications' => $user ? $user->notifications()->latest()->limit(15)->get() : collect(),
                'unreadNotificationsCount' => $user?->unreadNotifications()->count() ?? 0,
            ]);
        });
    }
}
