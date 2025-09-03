<?php

namespace App\Http\Controllers;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="ERP System API Documentation",
 *     description="API documentation for the ERP system including Desktop Tracker module",
 *
 *     @OA\Contact(
 *         email="support@erpsystem.com"
 *     ),
 *
 *     @OA\License(
 *         name="Proprietary",
 *         url="https://erpsystem.com/license"
 *     )
 * )
 *
 * @OA\Server(
 *     url="http://127.0.0.1:8000/api",
 *     description="Main API Server"
 * )
 * @OA\Server(
 *     url="http://127.0.0.1:8000/api/essapp/v1",
 *     description="ESSApp API Server"
 * )
 * @OA\Server(
 *     url="https://your-domain.com/api",
 *     description="Production API Server"
 * )
 *
 * @OA\Tag(
 *     name="Authentication",
 *     description="API Endpoints for user authentication"
 * )
 * @OA\Tag(
 *     name="Desktop Tracker",
 *     description="API Endpoints for desktop activity tracking"
 * )
 *
 * @OA\Schema(
 *     schema="ApiResponse",
 *     type="object",
 *
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(property="message", type="string", example="Operation completed successfully"),
 *     @OA\Property(property="data", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *
 *     @OA\Property(property="status", type="string", example="error"),
 *     @OA\Property(property="message", type="string", example="An error occurred"),
 *     @OA\Property(property="errors", type="object")
 * )
 *
 * @OA\Schema(
 *     schema="PaginatedResponse",
 *     type="object",
 *
 *     @OA\Property(property="status", type="string", example="success"),
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="current_page", type="integer", example=1),
 *         @OA\Property(property="data", type="array", @OA\Items(type="object")),
 *         @OA\Property(property="first_page_url", type="string"),
 *         @OA\Property(property="from", type="integer"),
 *         @OA\Property(property="last_page", type="integer"),
 *         @OA\Property(property="last_page_url", type="string"),
 *         @OA\Property(property="next_page_url", type="string", nullable=true),
 *         @OA\Property(property="path", type="string"),
 *         @OA\Property(property="per_page", type="integer"),
 *         @OA\Property(property="prev_page_url", type="string", nullable=true),
 *         @OA\Property(property="to", type="integer"),
 *         @OA\Property(property="total", type="integer")
 *     )
 * )
 */
class SwaggerController extends Controller
{
    // This controller exists only for Swagger documentation
}
