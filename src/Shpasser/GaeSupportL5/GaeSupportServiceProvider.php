<?php

namespace Shpasser\GaeSupportL5;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem as Flysystem;
use Shpasser\GaeSupportL5\Filesystem\GaeAdapter as GaeFilesystemAdapter;
use Shpasser\GaeSupportL5\Setup\PrepareForDeployCommand;
use Shpasser\GaeSupportL5\Setup\SetupCommand;

class GaeSupportServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = false;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Storage::extend('gae', function ($app, $config) {
            return new Flysystem(new GaeFilesystemAdapter($config['root']));
        });
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('gae.setup', function () {
            return new SetupCommand;
        });
        $this->app->singleton('gae.prepare', function () {
            return new PrepareForDeployCommand;
        });

        $this->commands(['gae.setup', 'gae.prepare']);
    }

    /**
     * Get the services provided by the provider.
     *
     * @return array
     */
    public function provides()
    {
        return array('gae-support');
    }
}
