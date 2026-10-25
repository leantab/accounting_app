<?php

namespace App\Models;

use Database\Factories\DefaultRateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $time_tracker_item_type_id
 * @property float $rate
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class DefaultRate extends Model
{
    /** @use HasFactory<DefaultRateFactory> */
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'rate' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function timeTrackerItemType(): BelongsTo
    {
        return $this->belongsTo(TimeTrackerItemType::class);
    }
}
