<?php

namespace Blockpoint\LaravelFaviconGenerator;

use Blockpoint\LaravelFaviconGenerator\Commands\LaravelFaviconGeneratorCommand;
use Blockpoint\LaravelFaviconGenerator\View\Components\FaviconMeta;
use Illuminate\Support\Facades\Blade;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelFaviconGeneratorServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-favicon-generator')
            ->hasConfigFile()
            ->hasViews()
            ->hasCommand(LaravelFaviconGeneratorCommand::class);
    }

    public function packageBooted(): void
    {
        // Register the favicon-meta component
        Blade::component('favicon-meta', FaviconMeta::class);
    }

    public function packageRegistered(): void
    {
        // Register view namespace
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'favicon-generator');

        // Publish assets
        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/favicon-generator'),
        ], 'favicon-generator-views');

        // Must happen during register(), not boot(): an app's own service provider may call
        // LaravelFaviconGenerator::resolveUsing() (the facade) from its own boot() — if this
        // singleton isn't bound yet at that point, the facade auto-resolves and caches a
        // throwaway instance instead, and app(LaravelFaviconGenerator::class) elsewhere (the
        // Blade component) gets a *different* instance that never had resolveUsing() called on
        // it. register() is guaranteed to run, for every provider, before any provider's boot().
        $this->app->singleton(LaravelFaviconGenerator::class, function () {
            return new LaravelFaviconGenerator;
        });
    }
}
