<?php

namespace App\Actions\Invoices;

use App\Data\CreateInvoiceData;
use App\Models\Invoice;
use Illuminate\Support\Collection;

class CreateInvoiceAction
{
    public static function execute(CreateInvoiceData $data): Invoice
    {
        $invoice = Invoice::create([
            'customer_id' => $data->customer_id,
            'from_company_id' => $data->from_company->id,
            'to_company_id' => $data->to_company->id,
            'invoice_number' => $data->invoice_number,
            'invoice_date' => $data->invoice_date,
            'invoice_amount' => $data->invoice_amount,
        ]);

        foreach ($data->invoice_items as $item) {
            $invoice->items()->create([
                'customer_id' => $data->customer_id,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['total'],
            ]);
        }

        return $invoice;
    }
}
