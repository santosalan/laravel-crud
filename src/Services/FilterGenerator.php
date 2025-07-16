<?php

namespace SantosAlan\LaravelCrud\Services;

use SantosAlan\LaravelCrud\DTOs\DatabaseField;

class FilterGenerator
{
    public function generateFilterSet(DatabaseField $field): string
    {
        $fieldType = $this->getFieldType($field);

        return match ($fieldType) {
            'date', 'datetime', 'time', 'timestamp', 'integer' => $this->generateDateTimeIntegerFilterSet($field),
            default => $this->generateStringFilterSet($field)
        };
    }

    public function generateFilter(DatabaseField $field): string
    {
        $fieldType = $this->getFieldType($field);
        $baseFilter = "isset(\$filter['{$field->name}'])";

        return match ($fieldType) {
            'date', 'datetime', 'time', 'timestamp', 'integer' => $this->generateDateTimeIntegerFilter($field, $baseFilter),
            'string' => $baseFilter . " ? '%' . \$filter['{$field->name}'] . '%' : null",
            default => $baseFilter . " ? \$filter['{$field->name}'] : null"
        };
    }

    private function getFieldType(DatabaseField $field): string
    {
        return match ($field->type) {
            'int' => 'integer',
            'char', 'varchar', 'text', 'enum' => 'string',
            default => $field->type
        };
    }

    private function generateDateTimeIntegerFilterSet(DatabaseField $field): string
    {
        return "'{$field->name}' => isset(\$r['{$field->name}']) ? \$r['{$field->name}'] : null,
                '{$field->name}-options' => isset(\$r['{$field->name}-options']) ? \$r['{$field->name}-options'] : null,
                '{$field->name}-1' => isset(\$r['{$field->name}-1'])
                                        ? \$r['{$field->name}-1']
                                        : (isset(\$r['{$field->name}-2'])
                                                ? \$r['{$field->name}-2']
                                                : null),
                '{$field->name}-2' => isset(\$r['{$field->name}-2'])
                                        ? \$r['{$field->name}-2']
                                        : (isset(\$r['{$field->name}-1'])
                                                ? \$r['{$field->name}-1']
                                                : null)";
    }

    private function generateStringFilterSet(DatabaseField $field): string
    {
        return "'{$field->name}' => isset(\$r['{$field->name}']) ? \$r['{$field->name}'] : null";
    }

    private function generateDateTimeIntegerFilter(DatabaseField $field, string $baseFilter): string
    {
        if ($field->fk) {
            return $baseFilter . " ? \$filter['{$field->name}'] : null";
        }

        return "{$baseFilter}
                                        ? [\$filter['{$field->name}-options'], \$filter['{$field->name}']]
                                        : (isset(\$filter['{$field->name}-1']) && isset(\$filter['{$field->name}-2'])
                                                ? [\$filter['{$field->name}-options'], [\$filter['{$field->name}-1'], \$filter['{$field->name}-2']]]
                                                : null)";
    }
}
