<?php

use App\Filament\Resources\Invoices\Pages\ManageInvoices;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

uses(TestCase::class);

test('can upload and process invoice file', function () {
    $user = User::factory()->customer()->create([
        'is_admin' => true,
    ]);

    actingAs($user);

    $file = UploadedFile::fake()->create('invoice.pdf', 100);

    // Provide a mocked response matching the expected JSON structure
    $responseMock = \Mockery::mock(\Laravel\Ai\Responses\AgentResponse::class . ', \ArrayAccess');
    
    $responseData = [
        'invoice' => [
            'invoice_number' => '123',
            'invoice_date' => '01/01/2023',
            'invoice_amount' => 100,
            'invoice_currency' => 'ARS',
            'invoice_payment_due_date' => '01/02/2023',
        ],
        'invoice_items' => [],
        'from_company' => [
            'name' => 'From Co',
            'social_reason' => 'From Co',
            'address' => 'Addr',
            'tax_id' => '11-11111111-1'
        ],
        'to_company' => [
            'name' => 'To Co',
            'social_reason' => 'To Co',
            'address' => 'Addr',
            'tax_id' => '22-22222222-2'
        ]
    ];

    $responseMock->shouldReceive('offsetGet')->with('from_company')->andReturn($responseData['from_company']);
    $responseMock->shouldReceive('offsetGet')->with('to_company')->andReturn($responseData['to_company']);
    $responseMock->shouldReceive('toArray')->andReturn($responseData);
    
    // Add ArrayAccess methods support if missing
    $responseMock->shouldReceive('offsetExists')->andReturn(true);

    $mock = \Mockery::mock(App\Ai\Agents\InvoiceFileParser::class)->makePartial();
    $mock->shouldReceive('prompt')->andReturn($responseMock);
    
    app()->instance(App\Ai\Agents\InvoiceFileParser::class, $mock);

    Livewire::test(ManageInvoices::class)
        ->callTableAction('uploadAndProcess', data: [
            'file' => $file,
        ])
        ->assertNotified();
});
