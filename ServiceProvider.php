<?php

namespace SantosAlan\LaravelCrud;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Container\Container;
use AlanSantos\LaravelCrud\Console\Commands\CrudMakeCommand;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{

    private function packagePath($path)
    {
        return __DIR__."/../$path";
    }

    private function registerCommands()
    {
       
        $this->commands(CrudMakeCommand::class);
       
    }

}
