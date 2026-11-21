<?php

use App\Enums\UserRoleEnum;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;

uses(TestCase::class, RefreshDatabase::class);

it('shows the settings link and pages only to platform admins', function (): void {
    actingAs(User::factory()->admin()->create());

    $this->get('/app')->assertOk()->assertSee('/app/settings-hub', false);
    $this->get('/app/settings-hub')->assertOk();
    $this->get('/app/pwa-settings-page')->assertOk();
});

it('hides the settings link and blocks the pages for company users', function (): void {
    $owner = User::factory()->create([
        'customer_id' => Customer::factory()->create()->id,
        'user_role_id' => UserRoleEnum::Owner->value,
    ]);

    actingAs($owner);

    $this->get('/app')->assertOk()->assertDontSee('/app/settings-hub', false);
    $this->get('/app/settings-hub')->assertForbidden();
    $this->get('/app/pwa-settings-page')->assertForbidden();
});

it('renders the install app button on the panel', function (): void {
    actingAs(User::factory()->admin()->create());

    $this->get('/app')->assertSee('Instalar app');
});
