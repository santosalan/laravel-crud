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
        $this->registerCommands();

    }

    private function registerCommands()
    {
       
        $this->commands(CrudMakeCommand::class);
       
    }

}
