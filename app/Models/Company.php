<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCustomer;

/**
 * Company model
 * @property int $id
 * @property int $customer_id
 * @property string $name
 * @property ?string $description
 * @property ?string $phone
 * @property ?string $email
 * @property ?string $address
 * @property ?string $tax_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Company extends Model
{
    use HasFactory;
    use BelongsToCustomer;

    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function roles()
    {
        return $this->belongsToMany(CompanyRole::class, 'company_role', 'company_id', 'role_id');
    }
}
