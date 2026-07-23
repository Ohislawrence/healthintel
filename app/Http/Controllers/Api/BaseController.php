<?php

namespace App\Http\Controllers\Api;

use App\Helpers\ApiResponse;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller;

abstract class BaseController extends Controller
{
    use ValidatesRequests;

    protected function success(mixed $data = null, string $message = 'OK', int $code = 200, array $meta = [])
    {
        return ApiResponse::success($data, $message, $code, $meta);
    }

    protected function error(string $message, int $code = 400, mixed $errors = null)
    {
        return ApiResponse::error($message, $code, $errors);
    }

    protected function paginated(mixed $data, string $message = 'OK', array $extra = [])
    {
        return ApiResponse::paginated($data, $message, $extra);
    }
}
