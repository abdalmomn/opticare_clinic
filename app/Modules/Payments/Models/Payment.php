<?php

namespace App\Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Modules\Authentication\Models\Staff;

class Payment extends Model
{
    public $timestamps = false;

    protected $table = 'payments';

    protected $fillable = [
        'invoice_id', 'amount', 'payment_method',
        'payment_date', 'received_by', 'notes',
    ];

    protected $casts = [
        'payment_date' => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(Staff::class, 'received_by');
    }
}
