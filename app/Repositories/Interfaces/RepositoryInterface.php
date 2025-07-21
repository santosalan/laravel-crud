<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Builder;

interface RepositoryInterface
{
    public function newObject();
    public function plucks();
    public function all();
    public function filter(array $filters, ?Builder $builder = null);
    public function create(array $data);
    public function find($id);
    public function update($id, array $data);
    public function delete($id);
}
