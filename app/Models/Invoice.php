<?php

namespace App\Models;

use App\Models\Concerns\BelongsToCustomer;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Invoice model
 *
 * @property int $id
 * @property int $customer_id
 * @property int $from_company_id
 * @property int $to_company_id
 * @property string $name
 * @property string|null $invoice_number
 * @property string|null $description
 * @property Carbon|null $date
 * @property float|null $total_amount
 * @property float $discount_percentage
 * @property float $discount_amount
 * @property float $tax_percentage
 * @property float $tax_amount
 * @property float|null $final_amount
 * @property Carbon|null $payment_due_date
 * @property float|null $payed_amount
 * @property bool $payed
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon $deleted_at
 */
class Invoice extends Model
{
    use BelongsToCustomer;
    use HasFactory;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'payment_due_date' => 'date',
            'payed' => 'boolean',
            'total_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'final_amount' => 'decimal:2',
            'payed_amount' => 'decimal:2',
        ];
    }

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
