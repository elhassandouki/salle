<?php

namespace App\Providers;

use App\Models\Subscription;
use App\Observers\SubscriptionObserver;
use App\Support\AdminLteMenuBuilder;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use JeroenNoten\LaravelAdminLte\Events\BuildingMenu;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(AdminLteMenuBuilder::class, fn () => new AdminLteMenuBuilder());
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);

        Event::listen(BuildingMenu::class, function (BuildingMenu $event) {
            foreach (AdminLteMenuBuilder::build() as $item) {
                $event->menu->add($item);
            }
        });
        Subscription::observe(SubscriptionObserver::class);
    }
}
