<?php

namespace SantosAlan\LaravelCrud\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Pluralizer;
use Illuminate\Support\Str;
use SantosAlan\LaravelCrud\DTOs\DatabaseTable;
use SantosAlan\LaravelCrud\DTOs\DatabaseField;
use SantosAlan\LaravelCrud\DTOs\RelationshipData;

class DatabaseAnalyzer
{
    private const EXCLUDED_TABLES = [
        'migrations',
        'password_resets',
        'failed_jobs',
        'password_reset_tokens',
        'personal_access_tokens',
        'jobs',
        'job_batches',
        'cache',
        'cache_locks',
        'sessions',
    ];

    public function __construct(
        private FieldProcessor $fieldProcessor = new FieldProcessor()
    )
    {}

    public function getTables(): array
    {
        $tables = DB::select('SHOW TABLES');
        $prefix = $this->getDatabasePrefix();
        $result = [];

        foreach ($tables as $table) {
            $tableName = $this->extractTableName($table, $prefix);
            
            if ($this->shouldSkipTable($tableName)) {
                continue;
            }

            $databaseTable = $this->createDatabaseTable($table, $tableName);
            $result[] = $databaseTable;
        }

        return $this->processRelationships($result);
    }

    public function readTableFields(DatabaseTable $table): void
    {
        $fields = DB::select('DESC ' . $table->originalName);

        foreach ($fields as $field) {
            $databaseField = $this->fieldProcessor->processField($field, $table);
            $table->addField($databaseField);
            
            $this->updateDisplayField($table, $databaseField);
        }
    }

    private function getDatabasePrefix(): string
    {
        return env('DB_PREFIX', '') ?: 
               env('DB_TABLE_PREFIX', '') ?: 
               env('DB_PREFIX_TABLE', '');
    }

    private function extractTableName($table, string $prefix): string
    {
        $databaseName = env('DB_DATABASE');
        return substr($table->{"Tables_in_{$databaseName}"}, strlen($prefix));
    }

    private function shouldSkipTable(string $tableName): bool
    {
        return in_array($tableName, self::EXCLUDED_TABLES);
    }

    private function createDatabaseTable($table, string $tableName): DatabaseTable
    {
        $prepareName = function ($name, $format) {
            $name = explode('_', $name);
            $name[count($name) - 1] = Pluralizer::{$format}(end($name));
            return implode('_', $name);
        };

        $singular = Str::camel($prepareName($tableName, 'singular'));
        $plural = Str::camel($prepareName($tableName, 'plural'));

        return new DatabaseTable(
            originalName: $table->{"Tables_in_" . env('DB_DATABASE')},
            name: $tableName,
            singular: $singular,
            plural: $plural,
            snakeSingular: Str::snake($singular),
            snakePlural: Str::snake($plural),
            fk: Str::snake($singular) . '_id'
        );
    }

    private function updateDisplayField(DatabaseTable $table, DatabaseField $field): void
    {
        $displayFields = [
            'name',
            $table->snakeSingular . '_name',
            'name_' . $table->snakeSingular,
            'title',
            $table->snakeSingular . '_title',
            'title_' . $table->snakeSingular,
            'username',
            'user',
            'login',
            'email'
        ];

        if (!$table->fieldDisplay && in_array($field->name, $displayFields)) {
            $table->fieldDisplay = true;
            $field->display = true;
        }
    }

    private function processRelationships(array $tables): array
    {
        $tableNames = collect($tables)->pluck('name');

        foreach ($tables as $table) {
            $this->processPivotTable($table, $tables, $tableNames);
        }

        foreach ($tables as $table) {
            $this->processHasRelationships($table, $tables);
        }

        return $tables;
    }

    private function processPivotTable(DatabaseTable $table, array $tables, $tableNames): void
    {
        $parts = explode('_', $table->name);

        if (count($parts) !== 2) {
            return;
        }

        [$first, $second] = array_map([Pluralizer::class, 'plural'], $parts);

        if (!$tableNames->contains($first) || !$tableNames->contains($second)) {
            return;
        }

        // Read fields for the pivot table first
        $this->readTableFields($table);

        foreach ($tables as $t) {
            if ($t->name === $first) {
                $t->belongsToMany[$table->name] = RelationshipData::create([
                    'table_name' => $second,
                    'foreign_key' => $t->fk,
                    'local_key' => 'id',
                    'pivot_table' => $table->name,
                    'pivot_local_key' => "{$t->snakeSingular}_id",
                    'pivot_foreign_key' => Str::snake(Str::singular($second)) . '_id',
                    'with_timestamps' => $this->hasTimestamps($table),
                    'pivot_columns' => $this->getPivotColumns($table)
                ]);
            } elseif ($t->name === $second) {
                $t->belongsToMany[$table->name] = RelationshipData::create([
                    'table_name' => $first,
                    'foreign_key' => $t->fk,
                    'local_key' => 'id',
                    'pivot_table' => $table->name,
                    'pivot_local_key' => "{$t->snakeSingular}_id",
                    'pivot_foreign_key' => Str::snake(Str::singular($first)) . '_id',
                    'with_timestamps' => $this->hasTimestamps($table),
                    'pivot_columns' => $this->getPivotColumns($table)
                ]);
            }
        }

        $table->relationTable = true;
    }
    
    private function hasTimestamps(DatabaseTable $table): bool
    {
        $hasCreatedAt = false;
        $hasUpdatedAt = false;
        
        foreach ($table->fields as $field) {
            if ($field->name === 'created_at') {
                $hasCreatedAt = true;
            }
            if ($field->name === 'updated_at') {
                $hasUpdatedAt = true;
            }
            if ($hasCreatedAt && $hasUpdatedAt) {
                return true;
            }
        }
        
        return false;
    }
    
    private function getPivotColumns(DatabaseTable $table): array
    {
        $skipColumns = [
            'id',
            'created_at',
            'updated_at',
            'deleted_at'
        ];
        
        $columns = [];
        foreach ($table->fields as $field) {
            // Skip standard timestamps and ID fields
            if (in_array($field->name, $skipColumns)) {
                continue;
            }
            
            // Skip foreign key ID fields (they end with _id)
            if (str_ends_with($field->name, '_id')) {
                continue;
            }
            
            $columns[] = $field->name;
        }
        
        return $columns;
    }

    private function processHasRelationships(DatabaseTable $table, array $tables): void
    {
        foreach ($tables as $relatedTable) {
            if ($table->name === $relatedTable->name || $relatedTable->relationTable) {
                continue;
            }

            if (in_array($table->name, $relatedTable->belongsTo)) {
                foreach ($relatedTable->fields as $field) {
                    if ($field->name === $table->fk) {
                        $relationshipType = $field->unique ? 'hasOne' : 'hasMany';
                        $table->{$relationshipType}[] = RelationshipData::create([
                            'table_name' => $relatedTable->name,
                            'foreign_key' => $field->name,
                            'local_key' => 'id',
                            'pivot_table' => null
                        ]);
                    }
                }
            }
        }
    }
}
