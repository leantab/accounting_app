<?php

use App\Filament\Resources\Users\Pages\ManageUsers;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Resend\Exceptions\ErrorException;
use Resend\Laravel\Facades\Resend;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(TestCase::class, RefreshDatabase::class);

it('sends the invitation through the Resend facade in Spanish', function (): void {
    $customer = Customer::factory()->create(['name' => 'Eventos Sur']);

    $emails = Mockery::mock();
    $emails->shouldReceive('send')->once()->withArgs(function (array $payload, array $options) use ($customer): bool {
        return $payload['to'] === ['nuevo@example.com']
            && str_starts_with($payload['subject'], 'Te invitaron a unirte a')
            && str_contains($payload['html'], 'Eventos Sur')
            && str_contains($payload['html'], 'Aceptar invitación')
            && str_contains($payload['html'], 'customer_id='.$customer->id)
            && $options === [];
    });
    Resend::shouldReceive('emails')->once()->andReturn($emails);

    actingAs(User::factory()->admin()->create());

    livewire(ManageUsers::class)
        ->callAction('invite', ['customer_id' => $customer->id, 'email' => 'nuevo@example.com'])
        ->assertNotified('Invitación enviada');
});

it('tells the user when the invitation could not be sent', function (): void {
    $customer = Customer::factory()->create();

    $emails = Mockery::mock();
    $emails->shouldReceive('send')->once()->andThrow(new ErrorException(['message' => 'dominio no verificado', 'name' => 'validation_error', 'statusCode' => 403]));
    Resend::shouldReceive('emails')->once()->andReturn($emails);

    actingAs(User::factory()->admin()->create());

    livewire(ManageUsers::class)
        ->callAction('invite', ['customer_id' => $customer->id, 'email' => 'nuevo@example.com'])
        ->assertNotified('No se pudo enviar la invitación');
});
