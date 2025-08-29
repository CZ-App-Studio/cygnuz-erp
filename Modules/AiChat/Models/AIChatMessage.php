<?php

namespace Modules\AiChat\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
use Modules\AICore\Models\AIModel;
use Modules\AICore\Models\AIProvider;

class AIChatMessage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ai_chat_messages';

    protected $fillable = [
        'chat_id',
        'user_id',
        'role',
        'content',
        'message_type',
        'metadata',
        'model_id',
        'provider_id',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost',
        'processing_time_ms',
        'status',
        'error_message',
        'is_pinned',
        'is_favorite',
        'edited_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'cost' => 'decimal:6',
        'processing_time_ms' => 'integer',
        'is_pinned' => 'boolean',
        'is_favorite' => 'boolean',
        'edited_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    protected $appends = ['formatted_time', 'formatted_cost', 'is_ai'];

    /**
     * Get the chat that owns the message
     */
    public function chat(): BelongsTo
    {
        return $this->belongsTo(AIChat::class, 'chat_id');
    }

    /**
     * Get the user that created the message
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the AI model used for this message
     */
    public function model(): BelongsTo
    {
        return $this->belongsTo(AIModel::class, 'model_id');
    }

    /**
     * Get the AI provider used for this message
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(AIProvider::class, 'provider_id');
    }

    /**
     * Get formatted time
     */
    public function getFormattedTimeAttribute()
    {
        return $this->created_at->format('g:i A');
    }

    /**
     * Get formatted cost
     */
    public function getFormattedCostAttribute()
    {
        if (!$this->cost) {
            return null;
        }
        return '$' . number_format($this->cost, 6);
    }

    /**
     * Check if message is from AI
     */
    public function getIsAiAttribute()
    {
        return $this->role === 'assistant';
    }

    /**
     * Check if message is from user
     */
    public function isUser(): bool
    {
        return $this->role === 'user';
    }

    /**
     * Check if message is from assistant
     */
    public function isAssistant(): bool
    {
        return $this->role === 'assistant';
    }

    /**
     * Check if message is a system message
     */
    public function isSystem(): bool
    {
        return $this->role === 'system';
    }

    /**
     * Mark message as read
     */
    public function markAsRead()
    {
        if ($this->status === 'delivered') {
            $this->update(['status' => 'read']);
        }
    }

    /**
     * Toggle pin status
     */
    public function togglePin()
    {
        $this->update(['is_pinned' => !$this->is_pinned]);
    }

    /**
     * Toggle favorite status
     */
    public function toggleFavorite()
    {
        $this->update(['is_favorite' => !$this->is_favorite]);
    }

    /**
     * Edit message content
     */
    public function editContent(string $newContent)
    {
        $this->update([
            'content' => $newContent,
            'edited_at' => now()
        ]);
    }

    /**
     * Scope for user messages
     */
    public function scopeUserMessages($query)
    {
        return $query->where('role', 'user');
    }

    /**
     * Scope for assistant messages
     */
    public function scopeAssistantMessages($query)
    {
        return $query->where('role', 'assistant');
    }

    /**
     * Scope for pinned messages
     */
    public function scopePinned($query)
    {
        return $query->where('is_pinned', true);
    }

    /**
     * Scope for favorite messages
     */
    public function scopeFavorite($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Get message preview (truncated content)
     */
    public function getPreview(int $length = 100): string
    {
        if (strlen($this->content) <= $length) {
            return $this->content;
        }
        
        return substr($this->content, 0, $length) . '...';
    }

    /**
     * Format message for export
     */
    public function formatForExport(): array
    {
        return [
            'timestamp' => $this->created_at->toIso8601String(),
            'role' => $this->role,
            'content' => $this->content,
            'user' => $this->user ? $this->user->name : 'System',
            'model' => $this->model ? $this->model->name : null,
            'provider' => $this->provider ? $this->provider->name : null,
            'tokens' => $this->total_tokens,
            'cost' => $this->cost,
            'processing_time_ms' => $this->processing_time_ms
        ];
    }
}