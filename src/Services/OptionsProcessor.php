<?php

namespace SantosAlan\LaravelCrud\Services;

use SantosAlan\LaravelCrud\DTOs\CrudOptions;

class OptionsProcessor
{
    public function processRoutes(CrudOptions $options): bool
    {
        if (in_array(strtoupper(trim($options->routes)), ['N', 'NO', 'FALSE'])) {
            return false;
        }
        
        return true;
    }

    public function processBaseModel(CrudOptions $options): bool
    {
        return !in_array(strtoupper(trim($options->baseModel)), ['N', 'NO', 'FALSE']);
    }

    public function processApiClient(CrudOptions $options): bool
    {
        return !in_array(strtoupper(trim($options->apiClient)), ['N', 'NO', 'FALSE']);
    }

    public function processWebService(CrudOptions $options): bool
    {
        return !in_array(strtoupper(trim($options->webService)), ['N', 'NO', 'FALSE']);
    }

    public function processPivotModels(CrudOptions $options): bool
    {
        return !in_array(strtoupper(trim($options->pivotModels)), ['N', 'NO', 'FALSE']);
    }

    public function processProfessional(CrudOptions $options): bool
    {
        return !in_array(strtoupper(trim($options->professional)), ['N', 'NO', 'FALSE']);
    }

    public function processTheme(CrudOptions $options): int
    {
        if (in_array(trim($options->theme), [1, 2])) {
            return (int) $options->theme;
        }
        
        return 1;
    }

    public function processPathModels(CrudOptions $options): string
    {
        if (trim($options->pathModels) !== '') {
            return \Illuminate\Support\Str::finish($options->pathModels, '\\');
        }
        
        return 'App\\Models\\';
    }
}
