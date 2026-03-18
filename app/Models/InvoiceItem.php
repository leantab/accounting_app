<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCustomer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * InvoiceItem model
 *
 * @property int $id
 * @property int $customer_id
 * @property int $invoice_id
 * @property string $name
 * @property int $quantity
 * @property float $unit_price
 * @property float $discount_percentage
 * @property float $discount_amount
 * @property float $total_price
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class InvoiceItem extends Model
{
    use BelongsToCustomer;
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
