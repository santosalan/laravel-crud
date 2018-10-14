# santosalan/laravel-crud

## Install with Composer
> php composer.phar require santosalan/laravel-crud

## See Help
> php artisan make:crud -h

## See Tables
> php artisan make:crud

## Generate a Basic Laravel-CRUD examples
> php artisan make:crud --tables [ all | table_number ] --path-models 'App\Models\' --routes [ y | n ]

or

> php artisan make:crud -t [ all | table_number ] -p 'App\Models\' -r [ y | n ]

## Generate a Basic Laravel-CRUD API Client to santosalan/lumen-crud core
> php artisan make:crud --tables [ all | table_number ] --path-models 'App\Models\' --routes [ y | n ] --api-client Y

or

> php artisan make:crud -t [ all | table_number ] -p 'App\Models\' -r [ y | n ] -a Y 

## Publish Provider
> php artisan vendor:publish --provider 'SantosAlan\LaravelCrud\ServiceProvider'


**Caution: All files will be replaced**
    

