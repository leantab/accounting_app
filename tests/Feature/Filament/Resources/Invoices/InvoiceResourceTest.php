<?php

use App\Filament\Resources\Invoices\Pages\ManageInvoices;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

uses(TestCase::class);

test('can upload and process invoice file', function () {
    $user = User::factory()->create([
        'is_admin' => true,
    ]);

    actingAs($user);

    $file = UploadedFile::fake()->create('invoice.pdf', 100);

    Livewire::test(ManageInvoices::class)
        ->callTableAction('uploadAndProcess', data: [
            'file' => $file,
        ])
        ->assertNotified();
});
