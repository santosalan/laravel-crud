<?php

namespace SantosAlan\LaravelCrud\Services;

use Illuminate\Support\Pluralizer;
use SantosAlan\LaravelCrud\DTOs\DatabaseField;
use SantosAlan\LaravelCrud\DTOs\DatabaseTable;

class FieldProcessor
{
    private ValidatorGenerator $validatorGenerator;
    private FilterGenerator $filterGenerator;

    public function __construct()
    {
        $this->validatorGenerator = new ValidatorGenerator();
        $this->filterGenerator = new FilterGenerator();
    }

    public function processField($field, DatabaseTable $table): DatabaseField
    {
        preg_match('/[a-zA-Z]+/', $field->Type, $type);
        preg_match('/[0-9]+/', $field->Type, $size);
        preg_match('/([a-zA-Z_0-9]+)_id/', $field->Field, $fk);

        $types = $this->extractEnumTypes($field->Type, $type[0] ?? '');

        $databaseField = new DatabaseField(
            name: $field->Field,
            type: $type[0] ?? '',
            inTypes: $types,
            size: isset($size[0]) ? (int) $size[0] : null,
            unsigned: strpos($field->Type, 'unsigned') !== false,
            required: $field->Null === 'NO',
            pk: $field->Key === 'PRI',
            fk: empty($fk) ? false : Pluralizer::plural($fk[1]),
            unique: $field->Key === 'UNI',
            default: $field->Default,
            autoIncrement: strpos($field->Extra, 'auto_increment') !== false,
            validator: '',
            validatorUpdate: '',
            filterSet: '',
            filter: ''
        );

        // Generate validators and filters
        $validator = $this->validatorGenerator->generate($databaseField, $table);
        $validatorUpdate = $this->validatorGenerator->generate($databaseField, $table, true);
        $filterSet = $this->filterGenerator->generateFilterSet($databaseField);
        $filter = $this->filterGenerator->generateFilter($databaseField);

        return new DatabaseField(
            name: $databaseField->name,
            type: $databaseField->type,
            inTypes: $databaseField->inTypes,
            size: $databaseField->size,
            unsigned: $databaseField->unsigned,
            required: $databaseField->required,
            pk: $databaseField->pk,
            fk: $databaseField->fk,
            unique: $databaseField->unique,
            default: $databaseField->default,
            autoIncrement: $databaseField->autoIncrement,
            validator: $validator,
            validatorUpdate: $validatorUpdate,
            filterSet: $filterSet,
            filter: $filter
        );
    }

    private function extractEnumTypes(string $fieldType, string $type): ?string
    {
        if ($type !== 'enum') {
            return null;
        }

        preg_match('/\([\'0-9,a-zA-Z]+\)/', $fieldType, $types);
        return str_replace(['(', "'", ')'], '', $types[0] ?? '');
    }
}
