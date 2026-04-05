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
            'invoice_number' => $data->invoice_number,
            'invoice_date' => $data->invoice_date,
            'invoice_amount' => $data->invoice_amount,
            'invoice_currency' => $data->invoice_currency,
            'invoice_payment_due_date' => $data->invoice_payment_due_date,
            'invoice_items' => $data->invoice_items,
            'from_company' => $data->from_company,
            'to_company' => $data->to_company,
        ]);

        foreach ($data->invoice_items as $item) {
            $invoice->items()->create([
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['total'],
            ]);
        }

        return $invoice;
    }
}
