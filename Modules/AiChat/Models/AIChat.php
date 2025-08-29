<?php

namespace Modules\AiChat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use App\Traits\UserActionsTrait;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class AIChat extends Model implements AuditableContract
{
    use HasFactory, SoftDeletes, UserActionsTrait, Auditable;

    protected $table = 'ai_chats';

    protected $fillable = [
        'title',
        'user_id',
        'company_id',
        'chat_type',
        'status',
        'settings',
        'context',
        'message_count',
        'total_cost',
        'total_tokens',
        'last_message_at',
        'created_by_id',
        'updated_by_id'
    ];

    protected $casts = [
        'settings' => 'array',
        'context' => 'array',
        'message_count' => 'integer',
        'total_cost' => 'decimal:6',
        'total_tokens' => 'integer',
        'last_message_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['formatted_cost', 'formatted_last_message', 'latest_message_preview'];

    /**
     * Get the user that owns the chat
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all messages for the chat
     */
    public function messages(): HasMany
    {
        return $this->hasMany(AIChatMessage::class, 'chat_id')->orderBy('created_at');
    }

    /**
     * Get only user messages
     */
    public function userMessages(): HasMany
    {
        return $this->hasMany(AIChatMessage::class, 'chat_id')
            ->where('role', 'user')
            ->orderBy('created_at');
    }

    /**
     * Get only assistant messages
     */
    public function assistantMessages(): HasMany
    {
        return $this->hasMany(AIChatMessage::class, 'chat_id')
            ->where('role', 'assistant')
            ->orderBy('created_at');
    }

    /**
     * Get the latest message
     */
    public function latestMessage()
    {
        return $this->hasOne(AIChatMessage::class, 'chat_id')->latest();
    }

    /**
     * Get formatted cost
     */
    public function getFormattedCostAttribute()
    {
        return '$' . number_format($this->total_cost, 4);
    }

    /**
     * Get formatted last message time
     */
    public function getFormattedLastMessageAttribute()
    {
        if (!$this->last_message_at) {
            return 'No messages yet';
        }
        
        return $this->last_message_at->diffForHumans();
    }

    /**
     * Get cleaned preview of latest message without markdown
     */
    public function getLatestMessagePreviewAttribute()
    {
        if (!$this->latestMessage) {
            return '';
        }
        
        $content = $this->latestMessage->content;
        
        // Strip markdown formatting
        $content = preg_replace('/\*\*(.*?)\*\*/', '$1', $content); // Bold
        $content = preg_replace('/\*(.*?)\*/', '$1', $content); // Italic  
        $content = preg_replace('/`{3}[\s\S]*?`{3}/', '[code block]', $content); // Code blocks
        $content = preg_replace('/`(.*?)`/', '$1', $content); // Inline code
        $content = preg_replace('/^#{1,6}\s+(.*)$/m', '$1', $content); // Headers
        $content = preg_replace('/\[([^\]]+)\]\([^\)]+\)/', '$1', $content); // Links
        $content = preg_replace('/^[\*\-\+]\s+/m', '', $content); // Unordered list items
        $content = preg_replace('/^\d+\.\s+/m', '', $content); // Ordered list items
        $content = preg_replace('/^>\s+/m', '', $content); // Blockquotes
        $content = preg_replace('/\n{2,}/', ' ', $content); // Multiple newlines
        $content = preg_replace('/\s+/', ' ', $content); // Multiple spaces
        
        return trim($content);
    }

    /**
     * Update chat statistics
     */
    public function updateStatistics()
    {
        $stats = $this->messages()
            ->selectRaw('COUNT(*) as count, SUM(total_tokens) as tokens, SUM(cost) as cost')
            ->first();
        
        $this->update([
            'message_count' => $stats->count ?? 0,
            'total_tokens' => $stats->tokens ?? 0,
            'total_cost' => $stats->cost ?? 0,
            'last_message_at' => $this->messages()->latest()->first()?->created_at
        ]);
    }

    /**
     * Archive the chat
     */
    public function archive()
    {
        $this->update(['status' => 'archived']);
    }

    /**
     * Check if chat is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Check if chat is archived
     */
    public function isArchived(): bool
    {
        return $this->status === 'archived';
    }

    /**
     * Scope for active chats
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope for archived chats
     */
    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    /**
     * Scope for user's chats
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Generate a title for the chat based on first message
     */
    public function generateTitle()
    {
        if ($this->title) {
            return $this->title;
        }

        $firstMessage = $this->messages()->where('role', 'user')->first();
        if ($firstMessage) {
            $title = substr($firstMessage->content, 0, 50);
            if (strlen($firstMessage->content) > 50) {
                $title .= '...';
            }
            $this->update(['title' => $title]);
            return $title;
        }

        return 'New Chat ' . $this->created_at->format('M d, Y');
    }

    /**
     * Get chat context for AI
     */
    public function getContextForAI(int $messageLimit = 10): array
    {
        $messages = $this->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at', 'desc')
            ->limit($messageLimit)
            ->get()
            ->reverse()
            ->map(function ($message) {
                return [
                    'role' => $message->role,
                    'content' => $message->content
                ];
            })
            ->values()
            ->toArray();

        return $messages;
    }

    /**
     * Clear chat history
     */
    public function clearHistory()
    {
        $this->messages()->delete();
        $this->update([
            'message_count' => 0,
            'total_cost' => 0,
            'total_tokens' => 0,
            'last_message_at' => null,
            'context' => null
        ]);
    }
}