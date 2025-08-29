<?php

namespace Modules\AiChat\Http\Controllers;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\AiChat\Models\AIChat;
use Modules\AiChat\Models\AIChatMessage;
use Modules\AICore\Models\AIModel;
use Modules\AICore\Models\AIProvider;
use Modules\AICore\Services\AIRequestService;
use Modules\AICore\Services\AIUsageTracker;

class AIChatEnhancedController extends Controller
{
    protected ?AIRequestService $aiRequestService;

    protected ?AIUsageTracker $usageTracker;

    public function __construct()
    {
        // Initialize AICore services if available
        if (class_exists('\Modules\AICore\Services\AIRequestService')) {
            $this->aiRequestService = app(AIRequestService::class);
            $this->usageTracker = app(AIUsageTracker::class);
        }
    }

    /**
     * Display the chat interface
     */
    public function index(Request $request)
    {
        $user = Auth::user();

        // Check if showing archived chats
        $showArchived = $request->get('archived', false);

        // Get user's chats based on status
        $chats = AIChat::forUser($user->id);

        if ($showArchived) {
            $chats = $chats->archived();
        } else {
            $chats = $chats->active();
        }

        $chats = $chats->with('latestMessage')
            ->orderBy('last_message_at', 'desc')
            ->get();

        // Get or create current chat
        $currentChatId = $request->get('chat_id');
        $currentChat = null;

        if ($currentChatId) {
            $currentChat = AIChat::forUser($user->id)
                ->with(['messages.user', 'messages.model', 'messages.provider'])
                ->find($currentChatId);
        }

        // Get available providers and models
        $providers = AIProvider::where('is_active', true)->get();
        $models = AIModel::where('is_active', true)->get();

        return view('aichat::chat.index', compact(
            'chats',
            'currentChat',
            'providers',
            'models',
            'showArchived'
        ));
    }

    /**
     * Create a new chat
     */
    public function createChat(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'nullable|string|max:255',
            'chat_type' => 'nullable|string|in:general,support,technical,creative',
            'settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $chat = AIChat::create([
                'user_id' => Auth::id(),
                'company_id' => null, // Single company app
                'title' => $request->input('title'),
                'chat_type' => $request->input('chat_type', 'general'),
                'settings' => $request->input('settings', []),
                'status' => 'active',
            ]);

            return response()->json([
                'success' => true,
                'chat' => $chat->load('messages'),
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create chat', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create chat',
            ], 500);
        }
    }

    /**
     * Send a message in a chat
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'chat_id' => 'required|exists:ai_chats,id',
            'message' => 'required|string|max:10000',
            'provider_id' => 'nullable|exists:ai_providers,id',
            'model_id' => 'nullable|exists:ai_models,id',
            'temperature' => 'nullable|numeric|min:0|max:2',
            'max_tokens' => 'nullable|integer|min:1|max:32000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        // Get the chat first (outside transaction)
        $chat = AIChat::forUser(Auth::id())->findOrFail($request->input('chat_id'));

        // Create user message first (outside main transaction to ensure it persists)
        $userMessage = AIChatMessage::create([
            'chat_id' => $chat->id,
            'user_id' => Auth::id(),
            'role' => 'user',
            'content' => $request->input('message'),
            'message_type' => 'text',
            'status' => 'sent',
        ]);

        try {
            DB::beginTransaction();

            // Generate title if this is the first message
            if (! $chat->title && $chat->messages()->count() == 1) { // Now it's 1 because we created the user message
                $chat->title = substr($request->input('message'), 0, 50);
                if (strlen($request->input('message')) > 50) {
                    $chat->title .= '...';
                }
                $chat->save();
            }

            // Get conversation context
            $context = $chat->getContextForAI(10);

            // Add system instruction for markdown formatting
            $systemPrompt = "You are a helpful AI assistant. Please format your responses using Markdown for better readability. Use appropriate formatting such as:\n".
                           "- **Bold** for emphasis\n".
                           "- *Italic* for subtle emphasis\n".
                           "- `code` for inline code\n".
                           "- Code blocks with ``` for multi-line code\n".
                           "- Lists with - or 1. for bullet/numbered lists\n".
                           "- Headers with # for sections\n".
                           "- Tables when presenting tabular data\n".
                           'Keep your responses clear, well-structured, and helpful.';

            // Add system message to context if not already present
            if (empty($context) || $context[0]['role'] !== 'system') {
                array_unshift($context, ['role' => 'system', 'content' => $systemPrompt]);
            }

            // Prepare AI request options
            $options = [
                'company_id' => null, // Single company app
                'module_name' => 'AiChat',
                'temperature' => $request->input('temperature', 0.7),
                'max_tokens' => $request->input('max_tokens', 2048),
            ];

            // Use specific model/provider if provided
            if ($request->has('model_id')) {
                $model = AIModel::find($request->input('model_id'));
                if ($model) {
                    $options['provider_type'] = $model->provider->type;
                }
            } elseif ($request->has('provider_id')) {
                $provider = AIProvider::find($request->input('provider_id'));
                if ($provider) {
                    $options['provider_type'] = $provider->type;
                }
            }

            // Send to AI
            $startTime = microtime(true);
            $aiResponse = $this->aiRequestService->chat(
                $request->input('message'),
                $context,
                $options
            );
            $processingTime = (microtime(true) - $startTime) * 1000;

            // Create assistant message
            $assistantMessage = AIChatMessage::create([
                'chat_id' => $chat->id,
                'role' => 'assistant',
                'content' => $aiResponse['response'],
                'message_type' => 'text',
                'model_id' => $aiResponse['model_id'] ?? null,
                'provider_id' => $aiResponse['provider_id'] ?? null,
                'prompt_tokens' => $aiResponse['usage']['prompt_tokens'] ?? null,
                'completion_tokens' => $aiResponse['usage']['completion_tokens'] ?? null,
                'total_tokens' => $aiResponse['usage']['total_tokens'] ?? null,
                'cost' => $aiResponse['usage']['cost'] ?? null,
                'processing_time_ms' => round($processingTime),
                'status' => 'delivered',
            ]);

            // Update chat statistics
            $chat->updateStatistics();

            DB::commit();

            return response()->json([
                'success' => true,
                'user_message' => $userMessage,
                'assistant_message' => $assistantMessage->load(['model', 'provider']),
                'usage' => $aiResponse['usage'] ?? null,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to send message', [
                'error' => $e->getMessage(),
                'chat_id' => $chat->id,
                'user_id' => Auth::id(),
            ]);

            // Create error message for assistant in a new transaction so it persists
            $errorMessage = null;
            try {
                DB::beginTransaction();
                $errorMessage = AIChatMessage::create([
                    'chat_id' => $chat->id,
                    'role' => 'assistant',
                    'content' => $this->getUserFriendlyErrorMessage($e),
                    'message_type' => 'text',
                    'status' => 'failed',
                    'error_message' => substr($e->getMessage(), 0, 500),
                ]);
                DB::commit();
            } catch (\Exception $errorCreateException) {
                DB::rollBack();
                Log::error('Failed to create error message', [
                    'chat_id' => $chat->id,
                    'error' => $errorCreateException->getMessage(),
                ]);

                // Create a minimal error message object for the response
                $errorMessage = new AIChatMessage([
                    'chat_id' => $chat->id,
                    'role' => 'assistant',
                    'content' => $this->getUserFriendlyErrorMessage($e),
                    'message_type' => 'text',
                    'status' => 'failed',
                    'created_at' => now(),
                ]);
            }

            // Update chat statistics even for errors
            try {
                DB::beginTransaction();
                $chat->updateStatistics();
                DB::commit();
            } catch (\Exception $statsException) {
                DB::rollBack();
                // Ignore statistics update errors
                Log::warning('Failed to update chat statistics after error', [
                    'chat_id' => $chat->id,
                    'error' => $statsException->getMessage(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to process message: '.$e->getMessage(),
                'assistant_message' => $errorMessage ? $errorMessage->load(['model', 'provider']) : [
                    'role' => 'assistant',
                    'content' => $this->getUserFriendlyErrorMessage($e),
                    'status' => 'failed',
                    'created_at' => now(),
                ],
            ], 500);
        }
    }

    /**
     * Get chat history
     */
    public function getChatHistory(Request $request, $chatId): JsonResponse
    {
        try {
            $chat = AIChat::forUser(Auth::id())
                ->with(['messages' => function ($query) use ($request) {
                    $query->with(['user', 'model', 'provider']);

                    if ($request->has('after_id')) {
                        $query->where('id', '>', $request->input('after_id'));
                    }

                    $query->orderBy('created_at', 'asc');
                }])
                ->findOrFail($chatId);

            return response()->json([
                'success' => true,
                'chat' => $chat,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Chat not found',
            ], 404);
        }
    }

    /**
     * Delete a chat
     */
    public function deleteChat($chatId): JsonResponse
    {
        try {
            $chat = AIChat::forUser(Auth::id())->findOrFail($chatId);
            $chat->delete();

            return response()->json([
                'success' => true,
                'message' => 'Chat deleted successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete chat',
            ], 500);
        }
    }

    /**
     * Archive a chat
     */
    public function archiveChat($chatId): JsonResponse
    {
        try {
            $chat = AIChat::forUser(Auth::id())->findOrFail($chatId);
            $chat->archive();

            return response()->json([
                'success' => true,
                'message' => 'Chat archived successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to archive chat',
            ], 500);
        }
    }

    /**
     * Clear chat history
     */
    public function clearChat($chatId): JsonResponse
    {
        try {
            $chat = AIChat::forUser(Auth::id())->findOrFail($chatId);
            $chat->clearHistory();

            return response()->json([
                'success' => true,
                'message' => 'Chat history cleared successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to clear chat history',
            ], 500);
        }
    }

    /**
     * Export chat as JSON or PDF
     */
    public function exportChat(Request $request, $chatId)
    {
        $validator = Validator::make($request->all(), [
            'format' => 'required|in:json,pdf,txt',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $chat = AIChat::forUser(Auth::id())
                ->with(['messages.user', 'messages.model', 'messages.provider'])
                ->findOrFail($chatId);

            $format = $request->input('format');

            if ($format === 'json') {
                $export = [
                    'chat' => [
                        'id' => $chat->id,
                        'title' => $chat->title,
                        'created_at' => $chat->created_at->toIso8601String(),
                        'total_messages' => $chat->message_count,
                        'total_cost' => $chat->total_cost,
                        'total_tokens' => $chat->total_tokens,
                    ],
                    'messages' => $chat->messages->map(function ($message) {
                        return $message->formatForExport();
                    }),
                ];

                return response()->json($export)
                    ->header('Content-Disposition', 'attachment; filename="chat-'.$chatId.'.json"');
            }

            if ($format === 'txt') {
                $content = 'Chat: '.$chat->title."\n";
                $content .= 'Created: '.$chat->created_at->format('Y-m-d H:i:s')."\n";
                $content .= str_repeat('=', 50)."\n\n";

                foreach ($chat->messages as $message) {
                    $content .= '['.$message->created_at->format('H:i:s').'] ';
                    $content .= ucfirst($message->role).': ';
                    $content .= $message->content."\n\n";
                }

                return response($content)
                    ->header('Content-Type', 'text/plain')
                    ->header('Content-Disposition', 'attachment; filename="chat-'.$chatId.'.txt"');
            }

            // PDF export would require additional setup with a PDF library
            return response()->json([
                'success' => false,
                'message' => 'PDF export not yet implemented',
            ], 501);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to export chat',
            ], 500);
        }
    }

    /**
     * Search within chats
     */
    public function searchChats(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 400);
        }

        try {
            $query = $request->input('query');

            // Search in chat titles and messages
            $chats = AIChat::forUser(Auth::id())
                ->where(function ($q) use ($query) {
                    $q->where('title', 'like', "%{$query}%")
                        ->orWhereHas('messages', function ($mq) use ($query) {
                            $mq->where('content', 'like', "%{$query}%");
                        });
                })
                ->with(['messages' => function ($q) use ($query) {
                    $q->where('content', 'like', "%{$query}%")
                        ->limit(3);
                }])
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'chats' => $chats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
            ], 500);
        }
    }

    /**
     * Toggle message pin status
     */
    public function toggleMessagePin($messageId): JsonResponse
    {
        try {
            $message = AIChatMessage::whereHas('chat', function ($query) {
                $query->where('user_id', Auth::id());
            })->findOrFail($messageId);

            $message->togglePin();

            return response()->json([
                'success' => true,
                'is_pinned' => $message->is_pinned,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to toggle pin status',
            ], 500);
        }
    }

    /**
     * Get chat statistics
     */
    public function getChatStatistics(Request $request): JsonResponse
    {
        try {
            $userId = Auth::id();
            $period = $request->input('period', 30); // days

            $stats = [
                'total_chats' => AIChat::forUser($userId)->count(),
                'active_chats' => AIChat::forUser($userId)->active()->count(),
                'total_messages' => AIChatMessage::whereHas('chat', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })->count(),
                'total_cost' => AIChat::forUser($userId)->sum('total_cost'),
                'total_tokens' => AIChat::forUser($userId)->sum('total_tokens'),
                'daily_usage' => [],
            ];

            // Get daily usage for the period
            $startDate = Carbon::now()->subDays($period);
            $dailyUsage = AIChatMessage::whereHas('chat', function ($q) use ($userId) {
                $q->where('user_id', $userId);
            })
                ->where('created_at', '>=', $startDate)
                ->selectRaw('DATE(created_at) as date, COUNT(*) as messages, SUM(total_tokens) as tokens, SUM(cost) as cost')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $stats['daily_usage'] = $dailyUsage;

            return response()->json([
                'success' => true,
                'statistics' => $stats,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get statistics',
            ], 500);
        }
    }

    /**
     * Get user-friendly error message based on exception
     */
    protected function getUserFriendlyErrorMessage(\Exception $e): string
    {
        $message = $e->getMessage();

        // Check for specific error patterns
        if (str_contains($message, 'overloaded') || str_contains($message, '503')) {
            return 'The AI service is currently busy. Please try again in a moment.';
        }

        if (str_contains($message, 'rate limit') || str_contains($message, '429')) {
            return 'Too many requests. Please wait a moment before trying again.';
        }

        if (str_contains($message, 'unauthorized') || str_contains($message, '401')) {
            return 'Authentication error. Please contact support.';
        }

        if (str_contains($message, 'quota') || str_contains($message, 'exceeded')) {
            return 'Service quota exceeded. Please try again later or contact support.';
        }

        if (str_contains($message, 'timeout')) {
            return 'The request timed out. Please try again with a shorter message.';
        }

        // Default message
        return 'Sorry, I encountered an error processing your request. Please try again.';
    }
}
