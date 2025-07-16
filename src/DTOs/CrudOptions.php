<?php

namespace SantosAlan\LaravelCrud\DTOs;

class CrudOptions
{
    public function __construct(
        public readonly string $table = '',
        public readonly string $pathModels = 'App\\Models',
        public readonly string $routes = 'Y',
        public readonly string $apiClient = 'N',
        public readonly string $webService = 'N',
        public readonly string $baseModel = 'N',
        public readonly string $pivotModels = 'N',
        public readonly string $professional = 'N',
        public readonly string $theme = '1'
    ) {}

    public static function fromArray(array $options): self
    {
        return new self(
            table: $options['table'] ?? '',
            pathModels: $options['path-models'] ?? 'App\\Models',
            routes: $options['routes'] ?? 'Y',
            apiClient: $options['api-client'] ?? 'N',
            webService: $options['web-service'] ?? 'N',
            baseModel: $options['base-model'] ?? 'N',
            pivotModels: $options['pivot-models'] ?? 'N',
            professional: $options['professional'] ?? 'N',
            theme: $options['theme'] ?? '1'
        );
    }
}
