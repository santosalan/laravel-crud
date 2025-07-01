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

        $obj = $obj ?? self::newModelObj();

        $fields = $conditions['fields'] ?? [];

        $with = $conditions['with'] ?? [];
        
        $without = $conditions['without'] ?? [];
                    
        $withCount = $conditions['withCount'] ?? [];

        $pagination = [
                        'page' => 1,
                        'limit' => 20,
                        ...($conditions['pagination'] ?? []),
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
                $obj = self::getWhereFromArray($obj, $key, $condition);
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

    private static function newModelObj()
    {
        $model = static::class;
        return new $model();
    }

    private static function getWhereFromArray($obj, $key, $condition)
    {
        $count = count($condition);
        $whereType = strtolower(trim($condition[0]));

        switch ($count) {
            case 1:
                return $obj->where($key, $condition[0]);

            case 2:
                switch ($whereType) {
                    case 'between':
                        return $obj->whereBetween($key, $condition[1]);
                    case 'in':
                        return $obj->whereIn($key, $condition[1]);
                    default:
                        return $obj->where($key, $condition[0], $condition[1]);
                };

            case 3:
                switch ($whereType) {
                    case 'between':
                        return $obj->whereBetween($key, $condition[1], $condition[2]);
                    case 'in':
                        return $obj->whereIn($key, $condition[1], $condition[2]);
                    default:
                        return $obj->where($key, $condition[0], $condition[1], $condition[2]);
                };

            default:
                throw new \Exception("Invalid " . strtoupper($key) . " condition", 1);
        }
    }
}