<?php

namespace App\Data;

use Spatie\LaravelData\Data;

class CreateInvoiceItemData extends Data
{
    public function __construct(
        public string $description,
        public string $quantity,
        public string $unit_price,
        public string $total,
    ) {}
}
