<?php

namespace SantosAlan\LaravelCrud\DTOs;

class DatabaseField
{
    public function __construct(
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $inTypes,
        public readonly ?int $size,
        public readonly bool $unsigned,
        public readonly bool $required,
        public readonly bool $pk,
        public readonly bool|string $fk,
        public readonly bool $unique,
        public readonly ?string $default,
        public readonly bool $autoIncrement,
        public readonly string $validator,
        public readonly string $validatorUpdate,
        public readonly string $filterSet,
        public readonly string $filter,
        public bool $display = false
    ) {}
}
