<?php

namespace App\Actions\Invoices;

use App\Data\CreateInvoiceData;
use App\Models\Invoice;
use Carbon\Carbon;
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
            'name' => 'Invoice ' . $data->invoice_number,
            'date' => Carbon::createFromFormat('d/m/Y', $data->invoice_date)->format('Y-m-d'),
            'total_amount' => $data->invoice_amount,
            'payment_due_date' => Carbon::createFromFormat('d/m/Y', $data->invoice_payment_due_date)->format('Y-m-d'),
        ]);

        foreach ($data->invoice_items as $item) {
            $invoice->items()->create([
                'customer_id' => $data->customer_id,
                'name' => $item['name'] ?? $item['description'] ?? 'Item',
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total_price' => $item['total_price'] ?? $item['total'] ?? 0,
            ]);
        }

        return $invoice;
    }
}
