<?php

use App\Actions\ProcessPDFInvoice;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

uses(TestCase::class);

it('can process pdf invoice', function () {
    $user = User::find(38);

    if (! $user) {
        $this->markTestSkipped('User 38 not found.');
    }

    $this->actingAs($user);

    $file = new UploadedFile(
        storage_path('app/private/01KP3BYZFMGGF3MQV0V1TS7THF.pdf'),
        '01KP3BYZFMGGF3MQV0V1TS7THF.pdf',
        'application/pdf',
        null,
        true
    );

    $invoice = ProcessPDFInvoice::execute($file, 46);

    expect($invoice)->not->toBeNull();
});
