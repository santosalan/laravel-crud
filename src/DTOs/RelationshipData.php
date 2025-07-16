<?php

namespace SantosAlan\LaravelCrud\DTOs;

class RelationshipData
{
    public function __construct(
        public readonly string $tableName,  // The name of the related table
        public readonly string $foreignKey, // Foreign key in the pivot/child table
        public readonly string $localKey,   // Primary key in the parent/local table
        public readonly ?string $pivotTable = null,  // For belongsToMany relationships
        public readonly ?string $pivotForeignKey = null, // Foreign key in pivot table for related model
        public readonly ?string $pivotLocalKey = null,   // Foreign key in pivot table for this model
        public readonly bool $withTimestamps = false,   // Whether pivot has timestamps
        public readonly array $pivotColumns = []      // Additional pivot columns to sync
    ) {}

    public static function create(array $data): self
    {
        return new self(
            tableName: $data['table_name'],
            foreignKey: $data['foreign_key'],
            localKey: $data['local_key'],
            pivotTable: $data['pivot_table'] ?? null,
            pivotForeignKey: $data['pivot_foreign_key'] ?? null,
            pivotLocalKey: $data['pivot_local_key'] ?? null,
            withTimestamps: $data['with_timestamps'] ?? false,
            pivotColumns: $data['pivot_columns'] ?? []
        );
    }
}
