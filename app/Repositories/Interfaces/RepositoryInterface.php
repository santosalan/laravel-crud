<?php

namespace App\Repositories\Interfaces;

interface RepositoryInterface
{
    public function plucks();
    public function all();
    public function filter(array $filters);
    public function create(array $data);
    public function find($id);
    public function update($id, array $data);
    public function delete($id);
}