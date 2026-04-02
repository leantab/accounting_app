<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * Customer model
 *
 * @property int $id
 * @property string $name
 * @property ?string $icon
 * @property ?string $description
 * @property ?string $phone
 * @property ?string $email
 * @property ?string $address
 * @property ?string $tax_id
 * @property bool $is_active
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Customer extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function company(): MorphOne
    {
        return $this->morphOne(Company::class, 'companyable');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
