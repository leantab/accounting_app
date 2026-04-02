<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class TimeTrackerItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'date' => 'date',
        'time_start' => 'datetime',
        'time_end' => 'datetime',
        'is_billable' => 'boolean',
        'rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('time_tracker_customer', function (Builder $builder): void {
            $user = Auth::user();

            if (! $user) {
                return;
            }

            $isAdmin = (bool) $user->is_admin;

            if ($isAdmin) {
                return;
            }

            $customerId = null;

            if (method_exists($user, 'currentCustomerId')) {
                $customerId = $user->currentCustomerId();
            } elseif (isset($user->customer_id)) {
                $customerId = $user->customer_id;
            }

            if ($customerId === null) {
                $builder->whereRaw('1 = 0');

                return;
            }

            $builder->whereHas('timeTracker', function (Builder $query) use ($customerId) {
                $query->where('customer_id', $customerId);
            });
        });
    }

    public function timeTracker(): BelongsTo
    {
        return $this->belongsTo(TimeTracker::class);
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
