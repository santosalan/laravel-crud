<?php

namespace SantosAlan\LaravelCrud\Services;

use Illuminate\Support\Str;
use SantosAlan\LaravelCrud\DTOs\DatabaseTable;

class FormFieldsProcessor
{
    private const EXCLUDED_FIELDS = [
        'id',
        'updated_at',
        'created_at',
        'deleted_at',
        'remember_token'
    ];

    private const COMMON_NAMES = [
        'name',
        'title',
        'user',
        'username',
        'login'
    ];

    public function generateFormFields(DatabaseTable $table, array $allTables): string
    {
        $fields = '';

        foreach ($table->fields as $field) {
            if (in_array($field->name, self::EXCLUDED_FIELDS)) {
                continue;
            }

            $fields .= $this->processField($field, $table, $allTables);
        }

        return $fields;
    }

    private function processField($field, DatabaseTable $table, array $allTables): string
    {
        if ($field->fk) {
            return $this->generateForeignKeyField($field, $table, $allTables);
        }

        switch (true) {
            case $field->type === 'date':
                return $this->generateDateField($field, $table);
            case in_array($field->type, ['datetime', 'timestamp']):
                return $this->generateDateTimeField($field, $table);
            case $field->type === 'int':
                return $this->generateNumberField($field, $table);
            case $field->type === 'enum':
                return $this->generateEnumField($field, $table);
            case $field->name === 'email':
                return $this->generateEmailField($field, $table);
            case $field->name === 'password':
                return $this->generatePasswordField($field, $table);
            case in_array($field->name, self::COMMON_NAMES):
                return $this->generateCommonField($field, $table);
            default:
                return $this->generateDefaultField($field, $table);
        }
    }

    private function generateForeignKeyField($field, DatabaseTable $table, array $allTables): string
    {
        foreach ($allTables as $relatedTable) {
            if ($relatedTable->name !== $field->fk) {
                continue;
            }

            return '
                    <div class="col-xs-12 col-12 mb-3">
                        {{ Form::label("' . $field->name . '", "' . Str::title(str_replace('_',' ',$relatedTable->singular)) . '", ["class" => "control-label"]) }}
                        {{ Form::select("' . $field->name . '", $plucks["' . $relatedTable->plural . '"], @$' . $table->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => ""' . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";
        }

        return '';
    }

    private function generateDateField($field, DatabaseTable $table): string
    {
        return '
                    <div class="col-xs-12 col-12 mb-3">
                        {{ Form::label("' . $field->name . '", "' . Str::title(str_replace('_', ' ', $field->name)) . '", ["class" => "control-label"]) }}
                        {{ Form::date("' . $field->name . '", @$' . $table->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => "' . Str::title(str_replace('_', ' ', $field->name)) . '"' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";
    }

    private function generateDateTimeField($field, DatabaseTable $table): string
    {
        return '
                    <div class="col-xs-12 col-12 mb-3">
                        {{ Form::label("' . $field->name . '", "' . Str::title(str_replace('_', ' ', $field->name)) . '", ["class" => "control-label"]) }}
                        {{ Form::datetime("' . $field->name . '", @$' . $table->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => "' . Str::title(str_replace('_', ' ', $field->name)) . '"' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";
    }

    private function generateNumberField($field, DatabaseTable $table): string
    {
        return '
                    <div class="col-xs-12 col-12 mb-3">
                        {{ Form::label("' . $field->name . '", "' . Str::title(str_replace('_', ' ', $field->name)) . '", ["class" => "control-label"]) }}
                        {{ Form::number("' . $field->name . '", @$' . $table->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => "' . Str::title(str_replace('_', ' ', $field->name)) . '"' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";
    }

    private function generateEnumField($field, DatabaseTable $table): string
    {
        $options = array_map(function($val) {
            return "'" . $val . "' => '" . Str::title($val) . "'";
        }, explode(',', $field->inTypes));

        return '
                    <div class="col-xs-12 col-12 mb-3">
                        {{ Form::label("' . $field->name . '", "' . Str::title(str_replace('_', ' ', $field->name)) . '", ["class" => "control-label"]) }}
                        {{ Form::select("' . $field->name . '", [' . implode(', ', $options) . '], @$' . $table->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => ""' . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";
    }

    private function generateEmailField($field, DatabaseTable $table): string
    {
        return '
                    <div class="col-xs-12 col-12 mb-3">
                        {{ Form::label("' . $field->name . '", trans(\'laravel-crud::view.email\'), ["class" => "control-label"]) }}
                        {{ Form::email("' . $field->name . '", @$' . $table->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => trans(\'laravel-crud::view.email\')' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";
    }

    private function generatePasswordField($field, DatabaseTable $table): string
    {
        return '
                    @if (Request::is(\'*/create\'))
                    <div class="col-xs-12 col-12 mb-3">
                        {{ Form::label("' . $field->name . '", trans(\'laravel-crud::view.password\'), ["class" => "control-label"]) }}
                        {{ Form::password("' . $field->name . '", ["class" => "form-control", "placeholder" => trans(\'laravel-crud::view.password\')' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>
                    @endif' . "\n";
    }

    private function generateCommonField($field, DatabaseTable $table): string
    {
        return '
                    <div class="col-xs-12 col-12 mb-3">
                        {{ Form::label("' . $field->name . '", trans(\'laravel-crud::view.' . $field->name . '\'), ["class" => "control-label"]) }}
                        {{ Form::text("' . $field->name . '", @$' . $table->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => trans(\'laravel-crud::view.' . $field->name . '\')' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";
    }

    private function generateDefaultField($field, DatabaseTable $table): string
    {
        return '
                    <div class="col-xs-12 col-12 mb-3">
                        {{ Form::label("' . $field->name . '", "' . Str::title(str_replace('_', ' ', $field->name)) . '", ["class" => "control-label"]) }}
                        {{ Form::text("' . $field->name . '", @$' . $table->singular . '->' . $field->name .', ["class" => "form-control", "placeholder" => "' . Str::title(str_replace('_', ' ', $field->name)) . '"' . ( $field->size ? ', "maxlength" => "' . $field->size . '"' : '' ) . ( $field->required ? ', "required"' : '' ) . ']) }}
                    </div>' . "\n";
    }
}
