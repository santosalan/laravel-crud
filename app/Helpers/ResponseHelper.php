<?php

namespace App\Helpers;

class ResponseHelper
{

    private static function formatResponse(string $action, array $data, string $msg, int $status)
    {
        return response()->json([
            'action' => $action,
            'status' => $status,
            'msg' => $msg,
            'request' => [],
            ...$data,
        ], $status);
    }

    public static function success(string $action, array $data = [], string $msg = 'success', int $status = 200)
    { 
        return self::formatResponse($action, $data, $msg, $status);
    }

    public static function error(string $action, array $data = [], string $msg = 'fail', int $status = 422)
    {
        return self::formatResponse($action, $data, $msg, $status);
    }

    public static function notFound(string $action, array $data = [])
    {
        return self::error($action, $data, 'fail', 404);
    }

    public static function unauthorized(string $action, array $data = [])
    {
        return self::error($action, $data, 'unauthorized', 401);
    }

    public static function forbidden(string $action, array $data = [])
    {
        return self::error($action, $data, 'forbidden', 403);
    }
    
}