<?php

namespace App\Data;

use App\Models\Company;
use Spatie\LaravelData\Data;

class CreateInvoiceData extends Data
{
    public function __construct(
        public int $customer_id,
        public string $invoice_number,
        public string $invoice_date,
        public string $invoice_amount,
        public string $invoice_payment_due_date,
        public Company $from_company,
        public Company $to_company,
        public array $invoice_items,
    ) {}
}
