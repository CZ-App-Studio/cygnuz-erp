<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;

class Error
{
    /**
     * Create an error JSON response
     */
    public static function response($data = null, int $statusCode = 400): JsonResponse
    {
        $response = [
            'status' => 'error',
        ];

        if ($data !== null) {
            // If data is a string, treat it as the error message
            if (is_string($data)) {
                $response['message'] = $data;
            } 
            // If data is an array with 'message' key, extract it
            elseif (is_array($data) && isset($data['message'])) {
                $response['message'] = $data['message'];
                unset($data['message']);
                
                // If there's remaining data (like errors array), add it
                if (!empty($data)) {
                    $response['data'] = $data;
                }
            } 
            // Otherwise, put everything in data
            else {
                $response['data'] = $data;
            }
        }

        return response()->json($response, $statusCode);
    }
}