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

        $this->publishServices();

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

    private function publishServices()
    {
        $servicesPath = $this->packagePath('app/Services');

        $this->publishes([
            $servicesPath => app_path('Services'),
        ], 'services');
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
