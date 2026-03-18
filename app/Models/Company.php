<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCustomer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Company model
 *
 * @property int $id
 * @property int $customer_id
 * @property string $name
 * @property ?string $description
 * @property ?string $phone
 * @property ?string $email
 * @property ?string $address
 * @property ?string $tax_id
 * @property bool $is_tax_retained
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class Company extends Model
{
    use BelongsToCustomer;
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'is_tax_retained' => 'boolean',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function roles()
    {
        return $this->belongsToMany(CompanyRole::class, 'company_role', 'company_id', 'role_id');
    }
}
