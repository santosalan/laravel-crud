<?php

namespace SantosAlan\LaravelCrud\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Pluralizer;
use SantosAlan\LaravelCrud\DTOs\DatabaseTable;
use SantosAlan\LaravelCrud\DTOs\RelationshipData;

class MarkProcessor
{
    public function processMarks(DatabaseTable $table, string $pathModels): array
    {
        return [
            // General
            'table_name' => $table->name,
            'plural_uc' => ucwords($table->plural),
            'plural' => $table->plural,
            'kebab_plural' => Str::kebab($table->plural),
            'snake_plural' => $table->snakePlural,
            'singular_uc' => ucwords($table->singular),
            'singular' => $table->singular,
            'snake_singular' => $table->snakeSingular,

            // Controller
            'uses' => $this->prepareUses($table, $pathModels),
            'validators' => $this->prepareValidators($table->fields),
            'validators_update' => $this->prepareValidatorsUpdate($table->fields),
            'plucks' => $this->preparePlucks($table),
            'filters_set' => $this->prepareFiltersSet($table->fields),
            'filters' => $this->prepareFilters($table->fields),
            'compacts' => $this->prepareCompacts($table->belongsTo),
            'compacts_c' => $this->prepareCompactsC($table->belongsTo),

            // Model
            'namespace' => substr($pathModels, 0, -1),
            'use_soft_deletes' => $this->prepareSoftDeletes($table->fields)[0],
            'trait_soft_deletes' => $this->prepareSoftDeletes($table->fields)[1],
            'primary_key' => $this->preparePrimaryKey($table->fields)[0],
            'auto_increment' => $this->preparePrimaryKey($table->fields)[1],
            'fillable' => $this->prepareFillable($table->fields),
            'hidden' => '',
            'with' => $this->prepareWith($table->belongsTo),
            'dates' => $this->prepareDates($table->fields),

            // Model Relationships
            'belongs_to' => $this->prepareBelongsTo($table->belongsTo),
            'has_one' => $this->prepareHasOne($table->hasOne),
            'has_many' => $this->prepareHasMany($table->hasMany),
            'belongs_many' => $this->prepareBelongsMany($table->belongsToMany),
            'sync_relationships' => $this->prepareSyncRelationships($table),
            'relationships' => $this->prepareRelationships($table),
        ];
    }

    private function prepareUses(DatabaseTable $table, string $pathModels): string
    {
        $uses = 'use ' . $pathModels . ucwords($table->singular) . ";\n";
        
        /** @var RelationshipData $belongsTo */
        foreach ($table->belongsTo as $belongsTo) {
            $uses .= 'use ' . $pathModels . Str::studly(Str::singular($belongsTo->tableName)) . ";\n";
        }

        return $uses;
    }

    private function prepareValidators(array $fields): string
    {
        $validators = '';
        
        foreach ($fields as $field) {
            if (in_array($field->name, ['id', 'created_at', 'updated_at', 'deleted_at', 'remember_token'])) {
                continue;
            }

            $prefix = empty($validators) ? '' : "                ";
            $validators .= $prefix . "'{$field->name}' => '{$field->validator}',\n";
        }

        return $validators;
    }

    private function prepareValidatorsUpdate(array $fields): string
    {
        $validators = '';
        
        foreach ($fields as $field) {
            if (in_array($field->name, ['id', 'created_at', 'updated_at', 'deleted_at', 'remember_token'])) {
                continue;
            }

            $prefix = empty($validators) ? '' : "                ";
            $validators .= $prefix . "'{$field->name}' => '{$field->validatorUpdate}',\n";
        }

        return $validators;
    }

    private function preparePlucks(DatabaseTable $table): string
    {
        // This would need access to all tables to implement properly
        // For now, returning empty string as placeholder
        return '';
    }

    private function prepareFiltersSet(array $fields): string
    {
        $filters = '';
        
        foreach ($fields as $field) {
            if (in_array($field->name, ['id', 'password', 'token', 'token_request', 'token_response', 'token_encrypt', 'remember_token'])) {
                continue;
            }

            $prefix = empty($filters) ? '' : "                ";
            $filters .= $prefix . $field->filterSet . ",\n";
        }

        return $filters;
    }

    private function prepareFilters(array $fields): string
    {
        $filters = '';
        
        foreach ($fields as $field) {
            if (in_array($field->name, ['id', 'password', 'token', 'token_request', 'token_response', 'token_encrypt', 'remember_token'])) {
                continue;
            }

            $prefix = empty($filters) ? '' : "            ";
            $filters .= $prefix . "'{$field->name}' => {$field->filter},\n";
        }

        return $filters;
    }

    private function prepareCompacts(array $belongsTo): string
    {
        $compacts = '';
        
        /** @var RelationshipData $relation */
        foreach ($belongsTo as $relation) {
            $relationName = Str::camel(Str::singular($relation->tableName));
            $compacts .= ", '{$relationName}'";
        }

        return $compacts;
    }

    private function prepareCompactsC(array $belongsTo): string
    {
        $compacts = $this->prepareCompacts($belongsTo);
        return empty(trim(substr($compacts, 1))) ? '' : ', compact(' . substr($compacts, 2) . ')';
    }

    private function prepareSoftDeletes(array $fields): array
    {
        foreach ($fields as $field) {
            if (strtolower($field->name) === 'deleted_at') {
                return [
                    'use Illuminate\Database\Eloquent\SoftDeletes;',
                    'use SoftDeletes;',
                ];
            }
        }

        return [null, null];
    }

    private function preparePrimaryKey(array $fields): array
    {
        foreach ($fields as $field) {
            if ($field->pk) {
                return [$field->name, $field->autoIncrement ? 'true' : 'false'];
            }
        }

        return ['id', 'true'];
    }

    private function prepareFillable(array $fields): string
    {
        $fillable = '';
        
        foreach ($fields as $field) {
            if (!in_array(strtolower($field->name), ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $fillable .= "'{$field->name}', ";
            }
        }

        return $fillable;
    }

    private function prepareWith(array $belongsTo): string
    {
        $with = '';
        
        /** @var RelationshipData $relation */
        foreach ($belongsTo as $relation) {
            $with .= "'" . Str::camel(Str::singular($relation->tableName)) . "', ";
        }

        return $with;
    }

    private function prepareDates(array $fields): string
    {
        $dates = '';
        
        foreach ($fields as $field) {
            if (in_array($field->name, ['created_at', 'updated_at'])) {
                continue;
            }

            if (in_array(strtolower($field->type), ['date', 'datetime', 'timestamp'])) {
                $dates .= "'{$field->name}', ";
            }
        }

        return $dates;
    }

    private function prepareName(string $name, string $format): string
    {
        $parts = explode('_', $name);
        $parts[count($parts) - 1] = Pluralizer::{$format}(end($parts));
        return implode('_', $parts);
    }

    private function prepareBelongsTo(array $belongsTo): string
    {
        $relations = '';
        /** @var RelationshipData $relation */
        foreach ($belongsTo as $relation) {
            $modelName = Str::studly(Str::singular($relation->tableName));
            $relationMethod = Str::camel(Str::singular($relation->tableName));
            $relations .= "\n    public function {$relationMethod}()\n";
            $relations .= "    {\n";
            $relations .= "        return \$this->belongsTo({$modelName}::class, '{$relation->foreignKey}', '{$relation->localKey}');\n";
            $relations .= "    }\n";
        }
        return $relations;
    }

    private function prepareHasOne(array $hasOne): string
    {
        $relations = '';
        /** @var RelationshipData $relation */
        foreach ($hasOne as $relation) {
            $modelName = Str::studly(Str::singular($relation->tableName));
            $relationMethod = Str::camel(Str::singular($relation->tableName));
            $relations .= "\n    public function {$relationMethod}()\n";
            $relations .= "    {\n";
            $relations .= "        return \$this->hasOne({$modelName}::class, '{$relation->foreignKey}', '{$relation->localKey}');\n";
            $relations .= "    }\n";
        }
        return $relations;
    }

    private function prepareHasMany(array $hasMany): string
    {
        $relations = '';
        /** @var RelationshipData $relation */
        foreach ($hasMany as $relation) {
            $modelName = Str::studly(Str::singular($relation->tableName));
            $relationMethod = Str::camel(Str::plural($relation->tableName));
            $relations .= "\n    public function {$relationMethod}()\n";
            $relations .= "    {\n";
            $relations .= "        return \$this->hasMany({$modelName}::class, '{$relation->foreignKey}', '{$relation->localKey}');\n";
            $relations .= "    }\n";
        }
        return $relations;
    }

    private function prepareBelongsMany(array $belongsToMany): string
    {
        $relations = '';
        /** @var RelationshipData $relation */
        foreach ($belongsToMany as $relation) {
            $modelName = Str::studly(Str::singular($relation->tableName));
            $relationMethod = Str::camel(Str::plural($relation->tableName));
            
            $relations .= "\n    public function {$relationMethod}()\n";
            $relations .= "    {\n";
            $relations .= "        return \$this->belongsToMany({$modelName}::class, '{$relation->pivotTable}', '{$relation->pivotLocalKey}', '{$relation->pivotForeignKey}')";
            
            // Add pivot columns if any exist
            if (!empty($relation->pivotColumns)) {
                $columns = implode("', '", $relation->pivotColumns);
                $relations .= "\n            ->withPivot('{$columns}')";
            }
            
            // Add timestamps if the pivot table has them
            if ($relation->withTimestamps) {
                $relations .= "\n            ->withTimestamps()";
            }
            
            $relations .= ";\n";
            $relations .= "    }\n";
        }
        
        return $relations;
    }

    private function prepareSyncRelationships(DatabaseTable $table): string
    {
        $syncs = '';
        if (!empty($table->belongsToMany)) {
            $syncs .= "\n        // Sync many-to-many relationships\n";
            /** @var RelationshipData $relation */
            foreach ($table->belongsToMany as $relation) {
                $relationMethod = Str::camel(Str::plural($relation->tableName));
                $relationKey = Str::snake(Str::plural($relation->tableName));
                $syncs .= "        \$this->{$relationMethod}()->sync(\$data['{$relationKey}'] ?? []);\n";
            }
        }
        return $syncs;
    }

    private function prepareRelationships(DatabaseTable $table): string
    {
        $relationships = [];
        
        // Add belongsTo relationships
        if (!empty($table->belongsTo)) {
            /** @var RelationshipData $relation */
            foreach ($table->belongsTo as $relation) {
                $relationships[] = "'" . Str::camel(Str::singular($relation->tableName)) . "'";
            }
        }

        // Add hasOne relationships
        if (!empty($table->hasOne)) {
            /** @var RelationshipData $relation */
            foreach ($table->hasOne as $relation) {
                $relationships[] = "'" . Str::camel(Str::singular($relation->tableName)) . "'";
            }
        }

        // Add hasMany relationships
        if (!empty($table->hasMany)) {
            /** @var RelationshipData $relation */
            foreach ($table->hasMany as $relation) {
                $relationships[] = "'" . Str::camel(Str::plural($relation->tableName)) . "'";
            }
        }

        // Add belongsToMany relationships
        if (!empty($table->belongsToMany)) {
            /** @var RelationshipData $relation */
            foreach ($table->belongsToMany as $relation) {
                $relationships[] = "'" . Str::camel(Str::plural($relation->tableName)) . "'";
            }
        }

        return empty($relationships) ? '' : implode(", ", $relationships);
    }
}
