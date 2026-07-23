<?php

namespace App\Helpers;

use Illuminate\Http\JsonResponse;

class ApiResponse
{
    /**
     * Success response envelope.
     */
    public static function success(
        mixed $data = null,
        string $message = 'OK',
        int $code = 200,
        array $meta = []
    ): JsonResponse {
        $body = [
            'ok' => true,
            'message' => $message,
            'data' => $data,
        ];

        if (!empty($meta)) {
            $body['meta'] = $meta;
        }

        return response()->json($body, $code);
    }

    /**
     * Error response envelope.
     */
    public static function error(
        string $message,
        int $code = 400,
        mixed $errors = null,
    ): JsonResponse {
        $body = [
            'ok' => false,
            'message' => $message,
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return response()->json($body, $code);
    }

    /**
     * Standard pagination meta.
     */
    public static function paginated(
        mixed $data,
        string $message = 'OK',
        array $extra = []
    ): JsonResponse {
        $response = $data->toArray();

        $body = [
            'ok' => true,
            'message' => $message,
            'data' => $response['data'],
            'meta' => array_merge([
                'current_page' => $response['current_page'],
                'last_page' => $response['last_page'],
                'per_page' => $response['per_page'],
                'total' => $response['total'],
                'from' => $response['from'],
                'to' => $response['to'],
            ], $extra),
        ];

        return response()->json($body);
    }
}
