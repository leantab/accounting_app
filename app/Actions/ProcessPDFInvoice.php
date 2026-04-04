<?php

namespace App\Actions;

use App\Ai\Agents\InvoiceFileParser;
use Illuminate\Http\UploadedFile;

class ProcessPDFInvoice
{
    public static function execute(UploadedFile $file)
    {
        $content = $file->get();

        $agent = new InvoiceFileParser();
        $prompt = 'Your are helping accountants in Argentina and LatAm. You have to extract the data from the invoice and return it in a JSON format.
                    Consider the following:
                    - The invoice is in Spanish.
                    - The invoice is in PDF format.
                    - The invoice is from Argentina.
                    - The top section belongs to the seller.
                    - The middle section contains 3 dates in format d/m/Y.
                    - The bottom section belongs to the buyer.
                    - There is a table in the bottom with the items.
                    - We expect to receive the following JSON structure:
                    {
                        "invoice": {
                            "invoice_number": "",
                            "invoice_date": "",
                            "invoice_amount": "",
                            "invoice_currency": "",
                            "invoice_payment_due_date": ""
                        },
                        "invoice_items": [
                            {
                                "name": "",
                                "quantity": "",
                                "unit_price": "",
                                "discount_percentage": "",
                                "discount_amount": "",
                                "total_price": ""
                            }
                        ],
                        "from_company": {
                            "name": "",
                            "social_reason": "",
                            "address": "",
                            "tax_id": ""
                        },
                        "to_company": {
                            "name": "",
                            "social_reason": "",
                            "address": "",
                            "tax_id": ""
                        }
                    }';

        $result = $agent->prompt($prompt, ['file_content' => $content]);

        dd($result);
    }
}
