<?php

namespace SantosAlan\LaravelCrud;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Container\Container;
use SantosAlan\LaravelCrud\Console\Commands\CrudMakeCommand;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{

    public function boot(Factory $view, Dispatcher $events, Repository $config)
    {
        $this->loadTranslations();

        $this->publishRepositories();
        
        $this->publishServices();

        $this->publishHelpers();

        $this->publishConfig();

        $this->publishViews();

        $this->registerCommands();
    }

    private function loadTranslations()
    {
        $translationsPath = $this->packagePath('resources/lang');

        $this->loadTranslationsFrom($translationsPath, 'laravel-crud');

        $this->publishes([
            $translationsPath => resource_path('lang/vendor/laravel-crud'),
        ], 'translations');
    }

    private function publishViews()
    {
        $layoutsPath = $this->packagePath('resources/views');

        $this->publishes([
            $layoutsPath => resource_path('views'),
        ], 'views');
    }

    private function publishRepositories()
    {
        $repositoriesPath = $this->packagePath('app/Repositories');

        $this->publishes([
            $repositoriesPath => app_path('Repositories'),
        ], 'repositories');
    }

    private function publishServices()
    {
        $servicesPath = $this->packagePath('app/Services');

        $this->publishes([
            $servicesPath => app_path('Services'),
        ], 'services');
    }

    private function publishHelpers()
    {
        $helpersPath = $this->packagePath('app/Helpers');

        $this->publishes([
            $helpersPath => app_path('Helpers'),
        ], 'helpers');
    }

    private function publishConfig()
    {
        $configPath = $this->packagePath('config');

        $this->publishes([
            $configPath => config_path(),
        ], 'config');

    }

    private function packagePath($path)
    {
        return __DIR__."/../$path";
    }

    private function registerCommands()
    {
        $this->commands(CrudMakeCommand::class);
    }

}
