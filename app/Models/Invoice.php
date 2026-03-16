<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCustomer;

/**
 * Invoice model
 * 
 * @property int $id
 * @property int $customer_id
 * @property int $from_company_id
 * @property int $to_company_id
 * @property string $name
 * @property string $description
 * @property \Carbon\Carbon $date
 * @property double $total_amount
 * @property double $payed_amount
 * @property bool $payed
 * @property \Carbon\Carbon $payment_due_date
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon $deleted_at
 */
class Invoice extends Model
{
    use HasFactory;
    use BelongsToCustomer;

    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function fromCompany()
    {
        return $this->belongsTo(Company::class, 'from_company_id');
    }

    public function toCompany()
    {
        return $this->belongsTo(Company::class, 'to_company_id');
    }

    public function items()
    {
        return $this->hasMany(InvoiceItem::class);
    }
}
