<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $user_id
 * @property int|null $project_id
 * @property string $name
 * @property string $date_start
 * @property string $date_end
 * @property float|null $hours
 * @property string|null $description
 * @property bool $billed
 * @property float|null $amount
 * @property int|null $invoice_id
 * @property bool $paid
 * @property int|null $payment_id
 * @property string|null $paid_date
 * @property bool $approved
 * @property string|null $approved_at
 * @property int|null $approved_by
 */
class TimeTracker extends Model
{
    protected $guarded = [];

    protected $casts = [
        'date_start' => 'date',
        'date_end' => 'date',
        'hours' => 'decimal:2',
        'amount' => 'decimal:2',
        'paid_date' => 'date',
        'approved_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
