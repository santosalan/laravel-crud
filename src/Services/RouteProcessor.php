<?php

namespace SantosAlan\LaravelCrud\Services;

use Illuminate\Support\Str;
use SantosAlan\LaravelCrud\DTOs\CrudOptions;
use SantosAlan\LaravelCrud\DTOs\DatabaseTable;

class RouteProcessor
{
    private TemplateManager $templateManager;
    private MarkProcessor $markProcessor;

    public function __construct(TemplateManager $templateManager, MarkProcessor $markProcessor)
    {
        $this->templateManager = $templateManager;
        $this->markProcessor = $markProcessor;
    }

    public function processRoutes(array $tables, CrudOptions $options, bool $webService): void
    {
        $template = $this->templateManager->getTemplate(
            'routes',
            $options->apiClient === 'Y', // apiLumen
            $options->webService === 'Y', // webService
            $options->professional === 'Y', // professional            
        );
        $routes = '';

        if ($options->table === 'all') {
            $routes = $this->processAllTables($tables, $template);
        } elseif ($options->table !== '') {
            $routes = $this->processSingleTable($tables, $options->table, $template);
        }

        $this->writeRoutesToFile($routes, $webService);
    }

    private function processAllTables(array $tables, string $template): string
    {
        $routes = '';

        foreach ($tables as $table) {
            if ($table->relationTable) {
                continue;
            }

            $routes .= $this->processTableRoute($table, $template);
        }

        return $routes;
    }

    private function processSingleTable(array $tables, string $tableKey, string $template): string
    {
        $table = $tables[$tableKey] ?? null;
        
        if (!$table) {
            return '';
        }

        return $this->processTableRoute($table, $template);
    }

    private function processTableRoute(DatabaseTable $table, string $template): string
    {
        $marks = [
            'plural_uc' => ucwords($table->plural),
            'plural' => $table->plural,
            'kebab_plural' => Str::kebab($table->plural),
        ];

        $processedTemplate = $template;
        
        foreach ($this->getRouteMarks() as $mark) {
            $processedTemplate = str_replace('{{{' . $mark . '}}}', trim($marks[$mark]), $processedTemplate);
        }

        return $processedTemplate;
    }

    private function writeRoutesToFile(string $routes, bool $webService): void
    {
        $filePath = $webService 
            ? base_path() . '/routes/api.php' 
            : base_path() . '/routes/web.php';

        $fileHandle = fopen($filePath, 'a+');
        
        if ($fileHandle) {
            fwrite($fileHandle, $routes);
            fclose($fileHandle);
        }
    }

    private function getRouteMarks(): array
    {
        return ['plural_uc', 'plural', 'kebab_plural'];
    }
}
