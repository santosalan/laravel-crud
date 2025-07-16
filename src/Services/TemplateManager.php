<?php

namespace SantosAlan\LaravelCrud\Services;

class TemplateManager
{
    private string $stubsPath;

    public function __construct()
    {
        $this->stubsPath = __DIR__ . '/../Console/Commands/stubs';
    }

    public function getTemplate(string $type, bool $apiLumen = false, bool $webService = false, bool $professional = false, int $theme = 1): string
    {
        $templatePath = $this->getTemplatePath($type, $apiLumen, $webService, $professional, $theme);
        
        $template = file_get_contents($templatePath);
        
        if ($template === false) {
            throw new \Exception("CRUD Template [{$type}] not found at {$templatePath}.");
        }

        return $template;
    }

    private function getTemplatePath(string $type, bool $apiLumen, bool $webService, bool $professional, int $theme): string
    {
        if ($apiLumen) {
            return "{$this->stubsPath}/api/{$type}.stub";
        }

        if ($webService) {
            $subdirectory = $professional && in_array($type, ['request', 'controller', 'service', 'repository']) 
                ? 'pro' 
                : '';
            
            return $subdirectory 
                ? "{$this->stubsPath}/web-service/{$subdirectory}/{$type}.stub"
                : "{$this->stubsPath}/web-service/{$type}.stub";
        }

        if (in_array($type, ['index.blade', 'form.blade', 'show.blade'])) {
            $themeDir = $this->getThemeDirectory($theme);
            return "{$this->stubsPath}/{$themeDir}/{$type}.stub";
        }

        return "{$this->stubsPath}/{$type}.stub";
    }

    private function getThemeDirectory(int $theme): string
    {
        return match ($theme) {
            1 => 'adminlte',
            2 => 'porto-admin',
            default => 'adminlte'
        };
    }
}
