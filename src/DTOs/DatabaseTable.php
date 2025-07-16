<?php

namespace SantosAlan\LaravelCrud\DTOs;

use SantosAlan\LaravelCrud\DTOs\RelationshipData;

class DatabaseTable
{
    public array $fields = [];
    /** @var RelationshipData[] */
    public array $belongsTo = [];
    /** @var RelationshipData[] */
    public array $hasMany = [];
    /** @var RelationshipData[] */
    public array $hasOne = [];
    /** @var RelationshipData[] */
    public array $belongsToMany = [];
    public array $marks = [];
    public array $arqs = [];

    public function __construct(
        public readonly string $originalName,
        public readonly string $name,
        public readonly string $singular,
        public readonly string $plural,
        public readonly string $snakeSingular,
        public readonly string $snakePlural,
        public readonly string $fk,
        public bool $relationTable = false,
        public bool $fieldDisplay = false
    ) {}

    public function addField(DatabaseField $field): void
    {
        $this->fields[] = $field;
        
        if ($field->fk) {
            $this->belongsTo[] = RelationshipData::create([
                'table_name' => $field->fk,
                'foreign_key' => $field->name,
                'local_key' => 'id',
                'pivot_table' => null
            ]);
        }
    }

    public function getPrimaryKey(): ?DatabaseField
    {
        foreach ($this->fields as $field) {
            if ($field->pk) {
                return $field;
            }
        }
        
        return null;
    }

    public function getDisplayField(): ?DatabaseField
    {
        foreach ($this->fields as $field) {
            if ($field->display) {
                return $field;
            }
        }
        
        return null;
    }
}
