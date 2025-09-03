<?php

namespace Modules\AICore\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\AICore\Services\AIRequestService;
use Modules\AICore\Services\AIUsageTracker;

class AIController extends Controller
{
    protected AIRequestService $aiRequestService;

    protected AIUsageTracker $usageTracker;

    public function __construct(AIRequestService $aiRequestService, AIUsageTracker $usageTracker)
    {
        $this->aiRequestService = $aiRequestService;
        $this->usageTracker = $usageTracker;
    }

    /**
     * Chat completion endpoint
     */
    public function chat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:10000',
            'context' => 'array',
            'module_name' => 'string',
            'max_tokens' => 'integer|min:1|max:8192',
            'temperature' => 'numeric|min:0|max:2',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $options = [
                'company_id' => auth()->user()->currentCompany->id ?? null,
                'module_name' => $request->input('module_name', 'AICore'),
                'max_tokens' => $request->input('max_tokens'),
                'temperature' => $request->input('temperature', 0.7),
            ];

            $result = $this->aiRequestService->chat(
                $request->input('message'),
                $request->input('context', []),
                $options
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            Log::error('AI Chat request failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'AI request failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Text completion endpoint
     */
    public function complete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'prompt' => 'required|string|max:10000',
            'module_name' => 'string',
            'max_tokens' => 'integer|min:1|max:8192',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $options = [
                'company_id' => auth()->user()->currentCompany->id ?? null,
                'module_name' => $request->input('module_name', 'AICore'),
                'max_tokens' => $request->input('max_tokens'),
            ];

            $result = $this->aiRequestService->complete(
                $request->input('prompt'),
                $options
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'AI request failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Text summarization endpoint
     */
    public function summarize(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:50000',
            'max_length' => 'integer|min:50|max:1000',
            'style' => 'in:concise,detailed,bullet_points,executive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $options = [
                'company_id' => auth()->user()->currentCompany->id ?? null,
                'module_name' => 'DocumentSummarizerAI',
                'max_length' => $request->input('max_length', 200),
                'style' => $request->input('style', 'concise'),
            ];

            $result = $this->aiRequestService->summarize(
                $request->input('text'),
                $options
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Summarization failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Data extraction endpoint
     */
    public function extract(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'text' => 'required|string|max:50000',
            'fields' => 'required|array|min:1',
            'fields.*' => 'string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $options = [
                'company_id' => auth()->user()->currentCompany->id ?? null,
                'module_name' => 'PDFAnalyzerAI',
            ];

            $result = $this->aiRequestService->extract(
                $request->input('text'),
                $request->input('fields'),
                $options
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data extraction failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get usage statistics
     */
    public function usage(Request $request): JsonResponse
    {
        try {
            $companyId = auth()->user()->currentCompany->id ?? null;

            if (! $companyId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Company context required',
                ], 400);
            }

            $period = $request->input('period', 'daily');
            $moduleName = $request->input('module_name');

            $usage = $this->usageTracker->getCurrentUsage($companyId, $moduleName, $period);

            return response()->json([
                'success' => true,
                'data' => $usage,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get usage data: '.$e->getMessage(),
            ], 500);
        }
    }
}
