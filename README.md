# santosalan/laravel-crud

## Install with Composer
> php composer.phar require santosalan/laravel-crud

## Doctrine Inflectors - Irregular Plural and Singular 
#### Add it in _config/app.php_
```php
/**
 * Irregulares Words
 */
'doctrine-inflector' => [
    'plural' => [
        'irregular' => [
            'traducao' => 'traducoes',
        ],
    ],

    'singular' => [
        'irregular' => [
            'traducoes' => 'traducao',
        ],
    ],
],
```

#### Add it in _app/Providers/AppServiceProvider.php_
```php
public function boot()
{
    // Doctrine Irregular Rules
    Inflector::rules('plural', config('app.doctrine-inflector.plural'));
    Inflector::rules('singular', config('app.doctrine-inflector.singular'));
}
```

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

### If make crud the API Client configure the _app/Services/CoreApiService.php_ with data of the system core Lumen CRUD Server


**Caution: All files will be replaced**
    

