<?php

namespace Joonas1234\NovaSimpleCms;

use Laravel\Nova\Nova;
use Laravel\Nova\Events\ServingNova;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Joonas1234\NovaSimpleCms\Http\Middleware\Authorize;
use Joonas1234\NovaSimpleCms\Commands\CreateBlueprint;
use Joonas1234\NovaSimpleCms\Commands\CreateTemplate;
use Joonas1234\NovaSimpleCms\Commands\CreatePage;

class ToolServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'nova-simple-cms');

        // Load Nova's translations
        $this->loadJsonTranslationsFrom(resource_path('lang/vendor/nova'));

        $this->app->booted(function () {
            $this->routes();
        });

        Nova::serving(function (ServingNova $event) {
            //
        });

        $this->publishes([
            __DIR__.'/../config/nova_simple_cms.php' => config_path('nova/simple_cms.php'),
        ], 'config');

        if (! class_exists('CreatePagesTable')) {
            $timestamp = date('Y_m_d_His', time());

            $this->publishes([
                __DIR__.'/../database/migrations/create_pages_table.php.stub' => database_path('migrations/'.$timestamp.'_create_pages_table.php'),
            ], 'migrations');
        }
    }

    /**
     * Register the tool's routes.
     *
     * @return void
     */
    protected function routes()
    {
        if ($this->app->routesAreCached()) {
            return;
        }

        Route::middleware(['nova', Authorize::class])
            ->prefix('nova-vendor/nova-simple-cms')
            ->group(__DIR__.'/../routes/api.php');

        Route::middleware('web')
            ->group(__DIR__.'/../routes/web.php');
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/../config/nova_simple_cms.php', 'nova.simple_cms');

        if (!$this->app->runningInConsole()) return;

        $this->commands([
            CreateBlueprint::class,
            CreateTemplate::class,
            CreatePage::class,
        ]);

    }
}
