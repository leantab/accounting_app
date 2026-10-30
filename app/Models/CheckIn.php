<?php

namespace App\Models;

use App\Enums\CheckInTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\Concerns\BelongsToCustomer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $customer_id
 * @property int $user_id
 * @property int|null $project_id
 * @property CheckInTypeEnum $type
 * @property Carbon $recorded_at
 * @property string $latitude
 * @property string $longitude
 * @property int|null $accuracy_m
 * @property string|null $notes
 */
class CheckIn extends Model
{
    use BelongsToCustomer;
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'type' => CheckInTypeEnum::class,
            'recorded_at' => 'datetime',
            'latitude' => 'decimal:7',
            'longitude' => 'decimal:7',
            'accuracy_m' => 'integer',
        ];
    }

    /**
     * Only employees and managers record their own attendance; owners and admins do not.
     */
    public static function canBeRecordedBy(User $user): bool
    {
        return ! $user->is_admin
            && $user->customer_id !== null
            && in_array($user->user_role_id, [UserRoleEnum::Employee->value, UserRoleEnum::Manager->value], true);
    }

    /**
     * Whoever can review the attendance of the whole company.
     */
    public static function canBeReviewedBy(User $user): bool
    {
        return (bool) $user->is_admin
            || ($user->customer_id !== null && in_array($user->user_role_id, [
                UserRoleEnum::Owner->value,
                UserRoleEnum::Admin->value,
                UserRoleEnum::Manager->value,
            ], true));
    }

    public function mapsUrl(): string
    {
        return 'https://www.google.com/maps?q='.$this->latitude.','.$this->longitude;
    }

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
}
