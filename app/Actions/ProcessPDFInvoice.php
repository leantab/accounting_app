<?php

namespace App\Actions;

use App\Actions\Invoices\CreateInvoiceAction;
use App\Ai\Agents\InvoiceFileParser;
use App\Data\CreateInvoiceData;
use App\Models\Company;
use Filament\Facades\Filament;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Laravel\Ai\Files;

class ProcessPDFInvoice
{
    public static function execute(string|UploadedFile $file, ?int $customerId = null)
    {
        if ($file instanceof UploadedFile) {
            $attachment = $file->getContents();
        } else {
            $attachment = $file;
        }

        $agent = app(InvoiceFileParser::class);
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

        $result = $agent->prompt($prompt, [
            Files\Document::fromStorage($attachment)
        ]);

        //Find or Create FromCompany
        $fromCompany = Company::where('tax_id', $result['from_company']['tax_id'])->first();
        if (!$fromCompany) {
            $fromCompany = Company::create([
                'name' => $result['from_company']['name'],
                'social_reason' => $result['from_company']['social_reason'],
                'tax_id' => $result['from_company']['tax_id'],
                'customer_id' => Filament::auth()->user()->customer_id ?? Auth::user()->customer_id,
            ]);
        }

        //Find or Create ToCompany
        $toCompany = Company::where('tax_id', $result['to_company']['tax_id'])->first();
        if (!$toCompany) {
            $toCompany = Company::create([
                'name' => $result['to_company']['name'],
                'social_reason' => $result['to_company']['social_reason'],
                'tax_id' => $result['to_company']['tax_id'],
                'customer_id' => Filament::auth()->user()->customer_id ?? Auth::user()->customer_id,
            ]);
        }

        $arrayResult = is_array($result) ? $result : json_decode(json_encode($result), true);
        $arrayResult = array_merge($arrayResult, $arrayResult['invoice'] ?? []);
        $data = CreateInvoiceData::from($arrayResult);

        $customerId = Filament::auth()->user()->customer_id ?? Auth::user()->customer_id;

        return CreateInvoiceAction::execute($data, $fromCompany->id, $toCompany->id, $customerId);
    }
}
