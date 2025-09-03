<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class Success
{
    /**
     * Create a success JSON response
     */
    public static function response($data = null, int $statusCode = 200): JsonResponse
    {
        $response = [
            'status' => 'success',
        ];

        if ($data !== null) {
            // If data has a 'message' key at the top level, extract it
            if (is_array($data) && isset($data['message'])) {
                $response['message'] = $data['message'];
                unset($data['message']);

                // If there's remaining data, add it
                if (! empty($data)) {
                    $response['data'] = $data;
                }
            } else {
                $response['data'] = $data;
            }
        }

        return response()->json($response, $statusCode);
    }
}
