<?php

namespace SantosAlan\LaravelCrud\Tests\Unit\Services;

use Illuminate\Support\Str;
use PHPUnit\Framework\TestCase;
use SantosAlan\LaravelCrud\DTOs\DatabaseField;
use SantosAlan\LaravelCrud\DTOs\DatabaseTable;
use SantosAlan\LaravelCrud\Services\FormFieldsProcessor;

class FormFieldsProcessorTest extends TestCase
{
    private FormFieldsProcessor $formFieldsProcessor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->formFieldsProcessor = new FormFieldsProcessor();
    }

    public function testGenerateFormFieldsSkipsExcludedFields(): void
    {
        $table = $this->createTableWithFields([
            $this->createField('id'),
            $this->createField('updated_at'),
            $this->createField('created_at'),
            $this->createField('deleted_at'),
            $this->createField('remember_token'),
            $this->createField('name'),
        ]);

        $result = $this->formFieldsProcessor->generateFormFields($table, []);

        $this->assertStringNotContainsString('Form::label("id"', $result);
        $this->assertStringNotContainsString('Form::label("updated_at"', $result);
        $this->assertStringNotContainsString('Form::label("created_at"', $result);
        $this->assertStringNotContainsString('Form::label("deleted_at"', $result);
        $this->assertStringNotContainsString('Form::label("remember_token"', $result);
        $this->assertStringContainsString('Form::label("name"', $result);
    }

    public function testGenerateDateField(): void
    {
        $table = $this->createTableWithFields([
            $this->createField('birth_date', 'date', true),
        ]);

        $result = $this->formFieldsProcessor->generateFormFields($table, []);

        $this->assertStringContainsString('Form::date("birth_date"', $result);
        $this->assertStringContainsString('"required"', $result);
    }

    public function testGenerateDateTimeField(): void
    {
        $table = $this->createTableWithFields([
            $this->createField('created', 'datetime'),
            $this->createField('modified', 'timestamp'),
        ]);

        $result = $this->formFieldsProcessor->generateFormFields($table, []);

        $this->assertStringContainsString('Form::datetime("created"', $result);
        $this->assertStringContainsString('Form::datetime("modified"', $result);
    }

    public function testGenerateNumberField(): void
    {
        $table = $this->createTableWithFields([
            $this->createField('age', 'int', true, null, 3),
        ]);

        $result = $this->formFieldsProcessor->generateFormFields($table, []);

        $this->assertStringContainsString('Form::number("age"', $result);
        $this->assertStringContainsString('"maxlength" => "3"', $result);
        $this->assertStringContainsString('"required"', $result);
    }

    public function testGenerateEnumField(): void
    {
        $table = $this->createTableWithFields([
            $this->createField('status', 'enum', false, 'active,inactive,pending'),
        ]);

        $result = $this->formFieldsProcessor->generateFormFields($table, []);

        $this->assertStringContainsString('Form::select("status"', $result);
        $this->assertStringContainsString("'active' => 'Active'", $result);
        $this->assertStringContainsString("'inactive' => 'Inactive'", $result);
        $this->assertStringContainsString("'pending' => 'Pending'", $result);
    }

    public function testGenerateEmailField(): void
    {
        $table = $this->createTableWithFields([
            $this->createField('email'),
        ]);

        $result = $this->formFieldsProcessor->generateFormFields($table, []);

        $this->assertStringContainsString('Form::email("email"', $result);
        $this->assertStringContainsString("trans('laravel-crud::view.email')", $result);
    }

    public function testGeneratePasswordField(): void
    {
        $table = $this->createTableWithFields([
            $this->createField('password'),
        ]);

        $result = $this->formFieldsProcessor->generateFormFields($table, []);

        $this->assertStringContainsString("@if (Request::is('*/create'))", $result);
        $this->assertStringContainsString('Form::password("password"', $result);
        $this->assertStringContainsString("trans('laravel-crud::view.password')", $result);
    }

    public function testGenerateCommonNameField(): void
    {
        $table = $this->createTableWithFields([
            $this->createField('name'),
            $this->createField('title'),
            $this->createField('username'),
        ]);

        $result = $this->formFieldsProcessor->generateFormFields($table, []);

        $this->assertStringContainsString("trans('laravel-crud::view.name')", $result);
        $this->assertStringContainsString("trans('laravel-crud::view.title')", $result);
        $this->assertStringContainsString("trans('laravel-crud::view.username')", $result);
    }

    public function testGenerateForeignKeyField(): void
    {
        $userTable = new DatabaseTable(
            originalName: 'users',
            name: 'users',
            singular: 'user',
            plural: 'users',
            snakeSingular: 'user',
            snakePlural: 'users',
            fk: 'user_id'
        );

        $table = $this->createTableWithFields([
            $this->createField('user_id', 'int', true, null, null, true),
        ]);

        $result = $this->formFieldsProcessor->generateFormFields($table, [$userTable]);

        $this->assertStringContainsString('Form::select("user_id"', $result);
        $this->assertStringContainsString('$plucks["users"]', $result);
        $this->assertStringContainsString('"required"', $result);
    }

    private function createTableWithFields(array $fields): DatabaseTable
    {
        $table = new DatabaseTable(
            originalName: 'test_table',
            name: 'test_table',
            singular: 'test',
            plural: 'tests',
            snakeSingular: 'test',
            snakePlural: 'tests',
            fk: 'test_id'
        );
        
        foreach ($fields as $field) {
            $table->addField($field);
        }
        
        return $table;
    }

    private function createField(
        string $name,
        string $type = 'varchar',
        bool $required = false,
        ?string $inTypes = null,
        ?int $size = null,
        bool|string $fk = false
    ): DatabaseField {
        return new DatabaseField(
            name: $name,
            type: $type,
            inTypes: $inTypes,
            size: $size,
            unsigned: false,
            required: $required,
            pk: false,
            fk: $fk,
            unique: false,
            default: null,
            autoIncrement: false,
            validator: '',
            validatorUpdate: '',
            filterSet: '',
            filter: '',
            display: true
        );
    }
}
