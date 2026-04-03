<?php

namespace App\Providers;

use App\Console\Commands\ServeCommand as HerdCompatibleServeCommand;
use Illuminate\Foundation\Console\ServeCommand as LaravelServeCommand;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(LaravelServeCommand::class, HerdCompatibleServeCommand::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
