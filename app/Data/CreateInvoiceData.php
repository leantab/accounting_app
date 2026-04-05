<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class CreateInvoiceData extends Data
{
    public function __construct(
        public string $invoice_number,
        public string $invoice_date,
        public string $invoice_amount,
        public string $invoice_currency,
        public string $invoice_payment_due_date,
        public array $invoice_items,
        public array $from_company,
        public array $to_company,
    ) {}
}
