<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCustomer;

/**
 * Payment Model
 * 
 * @property int $id
 * @property int $customer_id
 * @property int $invoice_id
 * @property string $reference
 * @property double $amount
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Payment extends Model
{
    use HasFactory;
    use BelongsToCustomer;

    protected $guarded = [];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
