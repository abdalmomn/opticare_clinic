<?php

namespace App\Modules\Imaging\Models;

use App\Modules\Authentication\Models\Staff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImagingActivityLog extends Model
{
    protected $table = 'imaging_activity_logs';

    public $timestamps = false;

    public const ACTION_REQUEST_CREATED = 'request_created';

    public const ACTION_REQUEST_CANCELLED = 'request_cancelled';

    public const ACTION_PAYMENT_CONFIRMED = 'payment_confirmed';

    public const ACTION_PAYMENT_WAIVED = 'payment_waived';

    public const ACTION_SENT_TO_TECHNICIAN = 'sent_to_technician';

    public const ACTION_STARTED = 'started';

    public const ACTION_FILE_UPLOADED = 'file_uploaded';

    public const ACTION_FILE_DELETED = 'file_deleted';

    public const ACTION_COMPLETED = 'completed';

    public const ACTION_DIRECT_UPLOAD_CREATED = 'direct_upload_created';

    public const ACTION_EXTERNAL_UPLOAD_CREATED = 'external_upload_created';

    public const ACTION_DEVICE_CREATED = 'device_created';

    public const ACTION_DEVICE_UPDATED = 'device_updated';

    public const ACTION_DEVICE_ACTIVATED = 'device_activated';

    public const ACTION_DEVICE_DEACTIVATED = 'device_deactivated';

    public const ACTION_DEVICE_DELETED_OR_RETIRED = 'device_deleted_or_retired';

    protected $fillable = [
        'imaging_request_id',
        'imaging_file_id',
        'actor_id',
        'action',
        'from_status',
        'to_status',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    public function request(): BelongsTo
    {
        return $this->belongsTo(ImagingRequest::class, 'imaging_request_id');
    }

    public function file(): BelongsTo
    {
        return $this->belongsTo(ImagingFile::class, 'imaging_file_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'actor_id');
    }

    public static function actions(): array
    {
        return [
            self::ACTION_REQUEST_CREATED,
            self::ACTION_REQUEST_CANCELLED,
            self::ACTION_PAYMENT_CONFIRMED,
            self::ACTION_PAYMENT_WAIVED,
            self::ACTION_SENT_TO_TECHNICIAN,
            self::ACTION_STARTED,
            self::ACTION_FILE_UPLOADED,
            self::ACTION_FILE_DELETED,
            self::ACTION_COMPLETED,
            self::ACTION_DIRECT_UPLOAD_CREATED,
            self::ACTION_EXTERNAL_UPLOAD_CREATED,
            self::ACTION_DEVICE_CREATED,
            self::ACTION_DEVICE_UPDATED,
            self::ACTION_DEVICE_ACTIVATED,
            self::ACTION_DEVICE_DEACTIVATED,
            self::ACTION_DEVICE_DELETED_OR_RETIRED,
        ];
    }
}
