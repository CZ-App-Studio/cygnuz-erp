<?php

namespace Modules\CRMCore\app\Models;

use App\Traits\UserActionsTrait;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Auditable;
use OwenIt\Auditing\Contracts\Auditable as AuditableContract;

class CustomerNotificationLog extends Model implements AuditableContract
{
    use Auditable, UserActionsTrait;

    protected $table = 'customer_notification_logs';

    protected $fillable = [
        'title', 'message', 'target_criteria', 'target_type', 'status',
        'sent_at', 'estimated_recipients', 'error_message',
        // 'link', 'icon',
        'created_by_id', 'tenant_id',
    ];

    protected $casts = [
        'target_criteria' => 'array',
        'sent_at' => 'datetime',
        'estimated_recipients' => 'integer',
    ];
}
