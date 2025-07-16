<?php

namespace SantosAlan\LaravelCrud\Services;

use Illuminate\Support\Str;
use SantosAlan\LaravelCrud\DTOs\DatabaseTable;

class FileGenerator
{
    private TemplateManager $templateManager;
    private string $pathModels;
    private bool $webService;
    private bool $professional;
    private bool $baseModel;

    public function __construct(
        TemplateManager $templateManager,
        string $pathModels = 'App\\Models\\',
        bool $webService = false,
        bool $professional = false,
        bool $baseModel = false
    ) {
        $this->templateManager = $templateManager;
        $this->pathModels = $pathModels;
        $this->webService = $webService;
        $this->professional = $professional;
        $this->baseModel = $baseModel;
    }

    public function generateFiles(array $tables, string $tableOption): void
    {
        if ($this->baseModel) {
            $this->generateBaseModel();
            $this->generateModelAuth();
        }

        foreach ($this->getFileTypes() as $fileType) {
            $this->generateFileType($fileType, $tables, $tableOption);
        }
    }

    private function getFileTypes(): array
    {
        $types = ['controller', 'model'];

        if ($this->webService && $this->professional) {
            $types = array_merge($types, ['request', 'service', 'repository']);
        }

        if (!$this->webService) {
            $types = array_merge($types, ['index.blade', 'form.blade', 'show.blade']);
        }

        $types[] = 'pivot';

        return $types;
    }

    private function generateFileType(string $type, array $tables, string $tableOption): void
    {
        foreach ($tables as $key => $table) {
            if (!$this->shouldGenerateForTable($table, $type, $key, $tableOption)) {
                continue;
            }

            $this->generateSingleFile($type, $table);
        }
    }

    private function shouldGenerateForTable(DatabaseTable $table, string $type, int $key, string $tableOption): bool
    {
        // Skip relation tables for non-pivot types
        if ($table->relationTable && $type !== 'pivot') {
            return false;
        }

        // Skip non-relation tables for pivot type
        if (!$table->relationTable && $type === 'pivot') {
            return false;
        }

        // Check if we should generate for specific table
        if ($tableOption !== 'all' && (int) $tableOption !== $key) {
            return false;
        }

        return true;
    }

    private function generateSingleFile(string $type, DatabaseTable $table): void
    {
        $template = $this->templateManager->getTemplate(
            $type,
            false, // apiLumen - not using in this context
            $this->webService,
            $this->professional,
            1 // theme - default to AdminLTE
        );

        $processedTemplate = $this->processTemplate($template, $table, $type);
        $this->writeFile($type, $table, $processedTemplate);
    }

    private function processTemplate(string $template, DatabaseTable $table, string $type): string
    {
        $marks = $this->getMarksForType($type);

        foreach ($marks as $mark) {
            if (isset($table->marks[$mark])) {
                $template = str_replace(
                    '{{{' . $mark . '}}}',
                    trim($table->marks[$mark]),
                    $template
                );
            }
        }

        return $template;
    }

    private function writeFile(string $type, DatabaseTable $table, string $content): void
    {
        $filePath = $this->getFilePath($type, $table);
        $fileName = $this->getFileName($type, $table);

        $fullPath = $filePath . $fileName;

        // Create directory if it doesn't exist
        if (!is_dir($filePath)) {
            mkdir($filePath, 0755, true);
        }

        file_put_contents($fullPath, $content);
    }

    private function getFilePath(string $type, DatabaseTable $table): string
    {
        $pathModels = explode('\\', $this->pathModels);
        unset($pathModels[0]);

        $paths = $this->webService
            ? [
                'controller' => app_path() . '/Http/Controllers/Api/',
                'model' => app_path() . '/' . implode('/', $pathModels) . '/',
                'pivot' => app_path() . '/' . implode('/', $pathModels) . '/',
                'request' => app_path() . '/Http/Requests/',
                'service' => app_path() . '/Services/',
                'repository' => app_path() . '/Repositories/',
            ]
            : [
                'controller' => app_path() . '/Http/Controllers/',
                'model' => app_path() . '/' . implode('/', $pathModels) . '/',
                'pivot' => app_path() . '/' . implode('/', $pathModels) . '/',
                'index.blade' => resource_path() . '/views/' . Str::kebab($table->plural) . '/',
                'form.blade' => resource_path() . '/views/' . Str::kebab($table->plural) . '/',
                'show.blade' => resource_path() . '/views/' . Str::kebab($table->plural) . '/',
            ];

        return $paths[$type] ?? app_path() . '/';
    }

    private function getFileName(string $type, DatabaseTable $table): string
    {
        return match ($type) {
            'controller' => ucwords($table->plural) . 'Controller.php',
            'request' => ucwords($table->singular) . 'Request.php',
            'service' => ucwords($table->singular) . 'Service.php',
            'repository' => ucwords($table->singular) . 'Repository.php',
            'model', 'pivot' => ucwords($table->singular) . '.php',
            'index.blade' => 'index.blade.php',
            'form.blade' => 'form.blade.php',
            'show.blade' => 'show.blade.php',
            default => $type . '.php'
        };
    }

    private function generateBaseModel(): void
    {
        $template = $this->templateManager->getTemplate(
            'baseModel',
            false, // not using apiLumen in this context 
            $this->webService,
            $this->professional
        );
        $processedTemplate = str_replace(
            '{{{namespace}}}',
            trim(substr($this->pathModels, 0, -1)),
            $template
        );

        $filePath = $this->getBaseModelPath();
        $fileName = 'Model.php';

        if (!is_dir($filePath)) {
            mkdir($filePath, 0755, true);
        }

        file_put_contents($filePath . $fileName, $processedTemplate);
    }

    private function generateModelAuth(): void
    {
        $template = $this->templateManager->getTemplate(
            'modelAuth',
            false, // not using apiLumen in this context 
            $this->webService,
            $this->professional
        );
        $processedTemplate = str_replace(
            '{{{namespace}}}',
            trim(substr($this->pathModels, 0, -1)),
            $template
        );

        $filePath = $this->getBaseModelPath();
        $fileName = 'ModelAuth.php';

        if (!is_dir($filePath)) {
            mkdir($filePath, 0755, true);
        }

        file_put_contents($filePath . $fileName, $processedTemplate);
    }

    private function getBaseModelPath(): string
    {
        $pathModels = explode('\\', $this->pathModels);
        unset($pathModels[0]);
        return app_path() . '/' . implode('/', $pathModels) . '/';
    }

    private function getMarksForType(string $type): array
    {
        return match ($type) {
            'controller' => [
                'plural_uc',
                'plural',
                'kebab_plural',
                'singular_uc',
                'singular',
                'uses',
                'validators',
                'validators_update',
                'plucks',
                'filters_set',
                'filters',
                'compacts',
                'compacts_c'
            ],
            'request' => [
                'plural_uc',
                'plural',
                'kebab_plural',
                'singular_uc',
                'singular',
                'validators',
                'validators_update'
            ],
            'service' => [
                'plural_uc',
                'plural',
                'kebab_plural',
                'singular_uc',
                'singular',
                'uses',
                'validators',
                'validators_update',
                'plucks',
                'filters_set',
                'filters',
                'compacts',
                'compacts_c'
            ],
            'repository' => [
                'plural_uc',
                'plural',
                'kebab_plural',
                'singular_uc',
                'singular',
                'uses',
                'validators',
                'validators_update',
                'plucks',
                'filters_set',
                'filters',
                'compacts',
                'compacts_c'
            ],
            'model', 'pivot' => [
                'table_name',
                'plural_uc',
                'plural',
                'snake_plural',
                'singular_uc',
                'singular',
                'namespace',
                'use_soft_deletes',
                'trait_soft_deletes',
                'primary_key',
                'auto_increment',
                'fillable',
                'hidden',
                'with',
                'dates',
                'belongs_to',
                'has_one',
                'has_many',
                'belongs_many',
                'sync_relationships',
                'relationships'
            ],
            'index.blade' => [
                'plural_uc',
                'plural',
                'kebab_plural',
                'singular_uc',
                'singular',
                'filters_fields',
                'title_fields',
                'value_fields',
                'primary_key'
            ],
            'form.blade' => [
                'plural_uc',
                'plural',
                'kebab_plural',
                'singular_uc',
                'singular',
                'form_fields',
                'primary_key'
            ],
            'show.blade' => [
                'plural_uc',
                'plural',
                'kebab_plural',
                'singular_uc',
                'singular',
                'display_field',
                'show_fields'
            ],
            default => []
        };
    }

    public function setWebService(bool $webService): void
    {
        $this->webService = $webService;
    }

    public function setProfessional(bool $professional): void
    {
        $this->professional = $professional;
    }

    public function setBaseModel(bool $baseModel): void
    {
        $this->baseModel = $baseModel;
    }

    public function setPathModels(string $pathModels): void
    {
        $this->pathModels = $pathModels;
    }
}
