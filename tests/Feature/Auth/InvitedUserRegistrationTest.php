<?php

use App\Livewire\Auth\InvitedUserRegister;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

test('registration screen can be rendered for invited user', function () {
    $this->withoutExceptionHandling();
    $customer = Customer::factory()->create();

    $response = $this->get(route('invited.register', [
        'customer_id' => $customer->id,
        'email' => 'test@example.com',
    ]));

    $response->assertOk();
    $response->assertSee('test@example.com');
});

test('invited user can register', function () {
    $customer = Customer::factory()->create();

    // Since the component is a Blaze/Volt/Livewire component, we can use the Pest Livewire plugin
    // However, since it is a full-page component, we might need to know its exact class name.
    // If it's a single file component, testing via HTTP POST on the component or using Livewire::test.
    // Let's use standard livewire pest function.
    // Wait, since we don't have the class name, we can just use HTTP test for the form submission if it's Livewire!
    // Actually, Livewire form submissions are tricky with simple HTTP POST unless it's a form request.
    Livewire::withQueryParams([
        'customer_id' => $customer->id,
        'email' => 'test@example.com',
    ])->test(InvitedUserRegister::class)
        ->set('customer_id', $customer->id)
        ->set('name', 'Test')
        ->set('lastname', 'User')
        ->set('email', 'test@example.com')
        ->set('password', 'password')
        ->set('password_confirmation', 'password')
        ->call('register')
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'test@example.com',
        'customer_id' => $customer->id,
    ]);
});
