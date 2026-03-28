<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $user_id
 * @property int $time_tracker_item_type_id
 * @property string $description
 * @property float $rate
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class UserRate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'rate' => 'decimal:2',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function timeTrackerItemType(): BelongsTo
    {
        return $this->belongsTo(TimeTrackerItemType::class);
    }
}
