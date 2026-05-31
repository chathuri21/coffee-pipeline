<?php

namespace App\Providers;

use App\Domain\Product\Repositories\ProductVariantRepository;
use App\Infrastructure\Persistence\SqliteProductVariantRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            ProductVariantRepository::class,
            SqliteProductVariantRepository::class
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
