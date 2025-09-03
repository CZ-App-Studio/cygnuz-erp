<?php

namespace Modules\AccountingCore\app\Models;

use App\Helpers\FormattingHelper;
use App\Services\Settings\ModuleSettingsService;
use App\Traits\UserActionsTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\FileManagerCore\Traits\HasFiles;
use OwenIt\Auditing\Auditable as AuditableTrait;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class BasicTransaction extends Model implements AuditableContract
{
    use AuditableTrait, HasFactory, HasFiles, SoftDeletes, UserActionsTrait;

    protected $table = 'basic_transactions';

    protected $fillable = [
        'transaction_number',
        'type',
        'amount',
        'category_id',
        'description',
        'transaction_date',
        'reference_number',
        'attachment_path',
        'payment_method',
        'tags',
        'custom_fields',
        'created_by_id',
        'updated_by_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'transaction_date' => 'date',
        'tags' => 'array',
        'custom_fields' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $dates = [
        'transaction_date',
    ];

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::creating(function ($transaction) {
            if (empty($transaction->transaction_number)) {
                $transaction->transaction_number = self::generateTransactionNumber();
            }
        });
    }

    /**
     * Get the category for this transaction.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BasicTransactionCategory::class, 'category_id');
    }

    /**
     * Get the user who created this transaction.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by_id');
    }

    /**
     * Get the user who updated this transaction.
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by_id');
    }

    /**
     * Scope for income transactions.
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    /**
     * Scope for expense transactions.
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * Scope for date range.
     */
    public function scopeForDateRange($query, Carbon $startDate, Carbon $endDate)
    {
        return $query->whereBetween('transaction_date', [$startDate->toDateString(), $endDate->toDateString()]);
    }

    /**
     * Scope for current month.
     */
    public function scopeCurrentMonth($query)
    {
        return $query->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year);
    }

    /**
     * Scope for current year.
     */
    public function scopeCurrentYear($query)
    {
        return $query->whereYear('transaction_date', now()->year);
    }

    /**
     * Scope for category.
     */
    public function scopeForCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Generate a unique transaction number.
     */
    public static function generateTransactionNumber(): string
    {
        // Get settings from AccountingCore module
        $settingsService = app(ModuleSettingsService::class);
        $prefix = $settingsService->get('AccountingCore', 'transaction_prefix', 'TXN');
        $date = now()->format('Ym'); // 202501
        $fullPrefix = $prefix.$date;

        $lastTransaction = static::where('transaction_number', 'like', $fullPrefix.'%')
            ->orderBy('transaction_number', 'desc')
            ->first();

        if ($lastTransaction) {
            $lastNumber = (int) substr($lastTransaction->transaction_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $startNumber = $settingsService->get('AccountingCore', 'transaction_start_number', 1000);
            $newNumber = $startNumber;
        }

        return $fullPrefix.str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        return FormattingHelper::formatCurrency($this->amount);
    }

    /**
     * Get formatted transaction date.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->transaction_date->format('M d, Y');
    }

    /**
     * Get type badge HTML.
     */
    public function getTypeBadgeAttribute(): string
    {
        $class = $this->type === 'income' ? 'bg-label-success' : 'bg-label-danger';
        $icon = $this->type === 'income' ? 'bx-trending-up' : 'bx-trending-down';

        return sprintf(
            '<span class="badge %s"><i class="bx %s"></i> %s</span>',
            $class,
            $icon,
            ucfirst($this->type)
        );
    }

    /**
     * Get payment method badge.
     */
    public function getPaymentMethodBadgeAttribute(): string
    {
        if (! $this->payment_method) {
            return '';
        }

        $methods = [
            'cash' => ['icon' => 'bx-money', 'class' => 'bg-label-success'],
            'bank_transfer' => ['icon' => 'bx-transfer', 'class' => 'bg-label-info'],
            'credit_card' => ['icon' => 'bx-credit-card', 'class' => 'bg-label-primary'],
            'check' => ['icon' => 'bx-receipt', 'class' => 'bg-label-secondary'],
            'other' => ['icon' => 'bx-dots-horizontal-rounded', 'class' => 'bg-label-dark'],
        ];

        $method = $methods[$this->payment_method] ?? $methods['other'];

        return sprintf(
            '<span class="badge %s"><i class="bx %s"></i> %s</span>',
            $method['class'],
            $method['icon'],
            ucwords(str_replace('_', ' ', $this->payment_method))
        );
    }

    /**
     * Check if transaction has attachment.
     */
    public function hasAttachment(): bool
    {
        // Check FileManagerCore files first
        if ($this->files()->count() > 0) {
            return true;
        }

        // Fallback to legacy attachment path for backward compatibility
        return ! empty($this->attachment_path) && file_exists(storage_path('app/public/'.$this->attachment_path));
    }

    /**
     * Get attachment URL.
     */
    public function getAttachmentUrlAttribute(): ?string
    {
        // Check FileManagerCore files first
        $file = $this->files()->first();
        if ($file) {
            return $file->url ?? asset('storage/'.$file->path);
        }

        // Fallback to legacy attachment path for backward compatibility
        if (! empty($this->attachment_path) && file_exists(storage_path('app/public/'.$this->attachment_path))) {
            return asset('storage/'.$this->attachment_path);
        }

        return null;
    }

    /**
     * Get transaction attachments from FileManagerCore.
     */
    public function getTransactionAttachments()
    {
        return $this->files()->where('metadata->attachment_type', 'transaction')->get();
    }

    /**
     * Get tags as badges.
     */
    public function getTagsBadgesAttribute(): string
    {
        if (empty($this->tags)) {
            return '';
        }

        $badges = collect($this->tags)->map(function ($tag) {
            return sprintf('<span class="badge bg-label-secondary">%s</span>', e($tag));
        })->implode(' ');

        return $badges;
    }

    /**
     * Calculate running balance for a given date.
     */
    public static function getRunningBalance(?Carbon $date = null): array
    {
        $date = $date ?? now();

        $income = static::income()
            ->where('transaction_date', '<=', $date->toDateString())
            ->sum('amount');

        $expense = static::expense()
            ->where('transaction_date', '<=', $date->toDateString())
            ->sum('amount');

        return [
            'income' => $income,
            'expense' => $expense,
            'balance' => $income - $expense,
        ];
    }

    /**
     * Get summary statistics for a period.
     */
    public static function getSummaryForPeriod(Carbon $startDate, Carbon $endDate): array
    {
        $income = static::income()
            ->forDateRange($startDate, $endDate)
            ->sum('amount');

        $expense = static::expense()
            ->forDateRange($startDate, $endDate)
            ->sum('amount');

        $incomeCount = static::income()
            ->forDateRange($startDate, $endDate)
            ->count();

        $expenseCount = static::expense()
            ->forDateRange($startDate, $endDate)
            ->count();

        return [
            'income' => floatval($income ?: 0),
            'expense' => floatval($expense ?: 0),
            'balance' => floatval(($income ?: 0) - ($expense ?: 0)),
            'profit' => floatval(($income ?: 0) - ($expense ?: 0)),
            'income_count' => intval($incomeCount),
            'expense_count' => intval($expenseCount),
            'total_count' => intval($incomeCount + $expenseCount),
        ];
    }
}
