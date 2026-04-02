<?php

namespace App\Actions;

use App\Ai\Agents\InvoiceFileParser;
use Illuminate\Http\UploadedFile;

class ProcessPDFInvoice
{
    public function execute(UploadedFile $file)
    {
        $content = $file->get();

        $agent = new InvoiceFileParser();
        $result = $agent->prompt($content);

        return $result;
    }
}
