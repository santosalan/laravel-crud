<?php

namespace SantosAlan\LaravelCrud\Services;

use SantosAlan\LaravelCrud\DTOs\DatabaseField;
use SantosAlan\LaravelCrud\DTOs\DatabaseTable;

class ValidatorGenerator
{
    public function generate(DatabaseField $field, DatabaseTable $table, bool $update = false): string
    {
        $type = $this->getFieldType($field);
        $validator = $type;

        $validator .= $this->addEnumValidation($field);
        $validator .= $this->addMaxLengthValidation($field);
        $validator .= $this->addEmailValidation($field);
        $validator .= $this->addUniqueValidation($field, $table, $update);
        $validator .= $this->addRequiredValidation($field, $update);

        return $validator;
    }

    private function getFieldType(DatabaseField $field): string
    {
        return match ($field->type) {
            'int' => 'integer',
            'char', 'varchar', 'text', 'enum' => 'string',
            default => $field->type
        };
    }

    private function addEnumValidation(DatabaseField $field): string
    {
        return $field->type === 'enum' ? '|in:' . $field->inTypes : '';
    }

    private function addMaxLengthValidation(DatabaseField $field): string
    {
        if ($field->size && in_array($field->type, ['char', 'varchar', 'text'])) {
            return '|max:' . $field->size;
        }
        
        return '';
    }

    private function addEmailValidation(DatabaseField $field): string
    {
        return strpos($field->name, 'email') !== false ? '|email' : '';
    }

    private function addUniqueValidation(DatabaseField $field, DatabaseTable $table, bool $update): string
    {
        if (!$field->unique) {
            return '';
        }

        if (!$update) {
            return '|unique:' . $table->name . ',' . $field->name;
        }

        $pk = $this->getPrimaryKeyName($table);
        return '|unique:' . $table->name . ',' . $field->name . ',\' . $' . $table->singular . 'Id . \'';
    }

    private function addRequiredValidation(DatabaseField $field, bool $update): string
    {
        if (!$field->required) {
            return '';
        }

        return $update ? '|sometimes' : '|required';
    }

    private function getPrimaryKeyName(DatabaseTable $table): string
    {
        $pk = $table->getPrimaryKey();
        return $pk ? $pk->name : 'id';
    }
}
