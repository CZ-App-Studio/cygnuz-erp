<?php

namespace Modules\AICore\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class AIUsageLog extends Model
{
    use HasFactory;

    protected $table = 'ai_usage_logs';

    protected $fillable = [
        'user_id',
        'company_id',
        'module_name',
        'operation_type',
        'model_id',
        'prompt_tokens',
        'completion_tokens',
        'total_tokens',
        'cost',
        'processing_time_ms',
        'status',
        'error_message',
        'request_hash'
    ];

    protected $casts = [
        'prompt_tokens' => 'integer',
        'completion_tokens' => 'integer',
        'total_tokens' => 'integer',
        'cost' => 'decimal:6',
        'processing_time_ms' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    /**
     * Get the AI model that was used
     */
    public function model(): BelongsTo
    {
        return $this->belongsTo(AIModel::class, 'model_id');
    }

    /**
     * Get the user who made the request
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    /**
     * Calculate tokens per second
     */
    public function getTokensPerSecondAttribute(): float
    {
        if (!$this->processing_time_ms || $this->processing_time_ms == 0) {
            return 0;
        }
        
        return round($this->total_tokens / ($this->processing_time_ms / 1000), 2);
    }

    /**
     * Get cost per token
     */
    public function getCostPerTokenAttribute(): float
    {
        if (!$this->total_tokens || $this->total_tokens == 0) {
            return 0;
        }
        
        return round($this->cost / $this->total_tokens, 8);
    }

    /**
     * Check if request was successful
     */
    public function isSuccessful(): bool
    {
        return $this->status === 'success';
    }

    /**
     * Scope for successful requests
     */
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    /**
     * Scope for failed requests
     */
    public function scopeFailed($query)
    {
        return $query->where('status', '!=', 'success');
    }

    /**
     * Scope for specific company
     */
    public function scopeForCompany($query, int $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope for specific module
     */
    public function scopeForModule($query, string $moduleName)
    {
        return $query->where('module_name', $moduleName);
    }

    /**
     * Scope for date range
     */
    public function scopeDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('ai_usage_logs.created_at', [$startDate, $endDate]);
    }

    /**
     * Scope for recent logs
     */
    public function scopeRecent($query, int $days = 7)
    {
        return $query->where('ai_usage_logs.created_at', '>=', now()->subDays($days));
    }

    /**
     * Get aggregated statistics
     */
    public static function getStats(array $filters = []): array
    {
        $query = static::query();
        
        if (isset($filters['company_id'])) {
            $query->forCompany($filters['company_id']);
        }
        
        if (isset($filters['module_name'])) {
            $query->forModule($filters['module_name']);
        }
        
        if (isset($filters['days'])) {
            $query->recent($filters['days']);
        }
        
        $stats = $query->selectRaw('
            COUNT(*) as total_requests,
            SUM(total_tokens) as total_tokens,
            SUM(cost) as total_cost,
            AVG(processing_time_ms) as avg_processing_time,
            SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as successful_requests
        ')->first();
        
        return [
            'total_requests' => $stats->total_requests ?? 0,
            'total_tokens' => $stats->total_tokens ?? 0,
            'total_cost' => round($stats->total_cost ?? 0, 2),
            'avg_processing_time' => round($stats->avg_processing_time ?? 0, 2),
            'successful_requests' => $stats->successful_requests ?? 0,
            'success_rate' => $stats->total_requests > 0 
                ? round(($stats->successful_requests / $stats->total_requests) * 100, 2) 
                : 0
        ];
    }
}
