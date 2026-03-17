<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Concerns\BelongsToCustomer;

/**
 * InvoiceItem model
 * 
 * @property int $id
 * @property int $customer_id
 * @property int $invoice_id
 * @property string $name
 * @property int $amount
 * @property double $unit_price
 * @property double $total_price
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class InvoiceItem extends Model
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
