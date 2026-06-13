<?php

namespace App\Modules\Imaging\Models;

use App\Modules\Imaging\Enums\ImagingRequestItemStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImagingRequestItem extends Model
{
    protected $table = 'imaging_request_items';

    public const STATUS_REQUESTED = 'requested';

    public const STATUS_CAPTURED = 'captured';

    public const STATUS_SKIPPED = 'skipped';

    protected $fillable = [
        'imaging_request_id',
        'image_type',
        'eye',
        'region',
        'notes',
        'status',
    ];

    public function imagingRequest(): BelongsTo
    {
        return $this->belongsTo(ImagingRequest::class, 'imaging_request_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(ImagingFile::class, 'imaging_request_item_id');
    }

    public static function statuses(): array
    {
        return ImagingRequestItemStatusEnum::values();
    }
}
