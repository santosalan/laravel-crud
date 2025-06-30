<?php

namespace App\Models\Traits;

trait HasFilter
{
    /**
     * FILTER
     *
     * @return object class
     */
    public static function filter(array $conditions, $obj = null)
    {

        if (blank($obj)) {
            $model = static::class;
            $obj = new $model();
        }

        $fields = isset($conditions['fields'])
                    ? $conditions['fields']
                    : [];
        $with = isset($conditions['with'])
                    ? $conditions['with']
                    : [];
        $without = isset($conditions['without'])
                    ? $conditions['without']
                    : [];
        $withCount = isset($conditions['withCount'])
                    ? $conditions['withCount']
                    : [];
        $pagination = isset($conditions['pagination'])
                        ? [
                            'page' => 1,
                            'limit' => 20,
                            ...$conditions['pagination']
                        ]
                        : [
                            'page' => 1,
                            'limit' => 20,
                        ];

        unset(
            $conditions['fields'],
            $conditions['with'],
            $conditions['without'],
            $conditions['withCount'],
            $conditions['pagination']
        );


        foreach ($conditions as $key => $condition) {

            if (is_array($condition)) {
                if (count($condition) == 1) {
                    $obj = $obj->where($key, $condition[0]);
                } elseif (count($condition) == 2) {
                    if (strtolower(trim($condition[0])) === 'between') {
                        $obj = $obj->whereBetween($key, $condition[1]);
                    } elseif (strtolower(trim($condition[0])) === 'in') {
                        $obj = $obj->whereIn($key, $condition[1]);
                    } else {
                        $obj = $obj->where($key, $condition[0], $condition[1]);
                    }
                } elseif (count($condition) == 3) {
                    if (strtolower(trim($condition[0])) === 'between') {
                        $obj = $obj->whereBetween($key, $condition[1], $condition[2]);
                    } elseif (strtolower(trim($condition[0])) === 'in') {
                        $obj = $obj->whereIn($key, $condition[1], $condition[2]);
                    } else {
                        $obj = $obj->where($key, $condition[0], $condition[1], $condition[2]);
                    }
                } else {
                    throw new \Exception("Invalid " . strtoupper($key) . " condition", 1);
                }
            } else {
                if (is_numeric($condition) || is_bool($condition)) {
                    $obj = $obj->where($key, $condition);
                } elseif (is_string($condition)) {
                    $obj = $obj->where($key, 'LIKE', $condition);
                } elseif (is_null($condition)) {
                    $obj = $obj->whereNull($key);
                } else {
                    throw new \Exception("Invalid " . strtoupper($key) . " condition", 2);
                }
            }
        }

        $obj = $obj->without($without)->with($with)->withCount($withCount);
        
        if (! blank($fields) && is_array($fields)) {
            $obj = $obj->select($fields);
        } elseif (! blank($fields) && is_string($fields)) {
            $obj = $obj->select(array_map('trim', explode(',', $fields)));
        }

        if (! blank($pagination) && is_array($pagination)) {
            $obj = $obj->paginate($pagination['limit'], ['*'], 'page', $pagination['page']);
        } else {
            $obj = $obj->paginate();
        }

        return $obj;
    }
}