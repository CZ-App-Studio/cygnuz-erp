<?php

namespace App\ApiClasses;

use Illuminate\Http\JsonResponse;

class Error
{
    /**
     * Returns json response needed in controllers.
     *
     * contained in the response.
     *
     * @param  int  $code  Response status code.
     * @param  array  $headers  Headers needed to be set for the response.
     * @param  int  $options  Options.
     */
    public static function response(
        $data,
        int $code = 400,
        array $headers = [],
        $options = 0
    ): JsonResponse {

        /*   if ($code < 400) {
             throw new Exception('Status code is invalid');
           }*/
        /*
            if ($data instanceof ErrorResponseInterface) {
              $dataArray = get_object_vars($data);

              if (!array_key_exists('message', $dataArray)) {
                throw new Exception('Message property does not exist');
              }

              foreach ($dataArray as $key => $value) {
                $response[$key] = $value;
              }

              return response()->json($response, $code, $headers, $options);
            }*/

        return response()->json(
            [
                'statusCode' => $code,
                'status' => 'failed',
                'data' => $data,
            ],
            $code,
            $headers,
            $options
        );
    }
}
