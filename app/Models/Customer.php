<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Customer model
 * @property int $id
 * @property string $name
 * @property ?string $icon
 * @property ?string $description
 * @property ?string $phone
 * @property ?string $email
 * @property ?string $address
 * @property ?string $tax_id
 * @property bool $is_active
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Customer extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
