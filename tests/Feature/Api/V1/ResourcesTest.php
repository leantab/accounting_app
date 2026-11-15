<?php

use App\Enums\TimeTrackerItemTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\Company;
use App\Models\Customer;
use App\Models\DefaultRate;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\Project;
use App\Models\ProjectStatus;
use App\Models\TimeTracker;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use App\Models\UserRate;
use App\Models\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Resend\Laravel\Facades\Resend;
use Tests\TestCase;

use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;

uses(TestCase::class, RefreshDatabase::class);

function member(int $customerId, UserRoleEnum $role): User
{
    return User::factory()->create(['customer_id' => $customerId, 'user_role_id' => $role->value]);
}

/**
 * @return list<int>
 */
function listedIds(string $path): array
{
    return collect(getJson("/api/v1/{$path}")->assertOk()->json('data'))->pluck('id')->all();
}

test('each section is closed to the roles that do not see it in the panel', function (): void {
    $customerId = Customer::factory()->create()->id;
    $manager = member($customerId, UserRoleEnum::Manager);
    $employee = member($customerId, UserRoleEnum::Employee);
    $owner = member($customerId, UserRoleEnum::Owner);

    Sanctum::actingAs($employee);

    foreach (['companies', 'projects', 'invoices', 'payments', 'customers', 'users', 'default-rates'] as $path) {
        getJson("/api/v1/{$path}")->assertForbidden();
    }

    Sanctum::actingAs($manager);

    getJson('/api/v1/projects')->assertOk();

    foreach (['companies', 'invoices', 'payments', 'customers', 'users', 'default-rates'] as $path) {
        getJson("/api/v1/{$path}")->assertForbidden();
    }

    postJson('/api/v1/companies', ['name' => 'X'])->assertForbidden();
    postJson('/api/v1/users/invite', ['email' => 'a@example.com'])->assertForbidden();

    Sanctum::actingAs($owner);

    foreach (['companies', 'projects', 'invoices', 'payments', 'users', 'default-rates'] as $path) {
        getJson("/api/v1/{$path}")->assertOk();
    }

    getJson('/api/v1/customers')->assertForbidden();

    Sanctum::actingAs(User::factory()->admin()->create());

    getJson('/api/v1/customers')->assertOk();
});

test('companies are created inside the customer of the user and stay invisible to others', function (): void {
    $owner = User::factory()->customerOwner()->create();
    $foreign = Company::factory()->create();

    Sanctum::actingAs($owner);

    $id = postJson('/api/v1/companies', [
        'customer_id' => $foreign->customer_id,
        'name' => 'Sonido Sur',
        'email' => 'hola@sonidosur.test',
        'is_tax_retained' => true,
    ])->assertCreated()->json('data.id');

    expect(Company::query()->find($id)->customer_id)->toBe($owner->customer_id)
        ->and(listedIds('companies'))->toBe([$id])
        ->and(listedIds('companies?search=sonido'))->toBe([$id])
        ->and(listedIds('companies?search=nada'))->toBe([]);

    getJson("/api/v1/companies/{$foreign->id}")->assertNotFound();
    putJson("/api/v1/companies/{$foreign->id}", ['name' => 'Robada'])->assertNotFound();
    deleteJson("/api/v1/companies/{$foreign->id}")->assertNotFound();

    postJson('/api/v1/companies', ['email' => 'no-es-email'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email']);

    putJson("/api/v1/companies/{$id}", ['name' => 'Sonido Sur SRL'])->assertOk()->assertJsonPath('data.name', 'Sonido Sur SRL');
    deleteJson("/api/v1/companies/{$id}")->assertOk();

    expect(Company::query()->whereKey($id)->exists())->toBeFalse();
});

test('a company in use cannot be deleted', function (): void {
    $owner = User::factory()->customerOwner()->create();
    $company = Company::factory()->create(['customer_id' => $owner->customer_id]);
    Project::factory()->create(['customer_id' => $owner->customer_id, 'company_id' => $company->id]);

    Sanctum::actingAs($owner);

    deleteJson("/api/v1/companies/{$company->id}")->assertConflict();

    expect(Company::query()->whereKey($company->id)->exists())->toBeTrue();
});

test('managers manage projects with companies of their customer', function (): void {
    $customerId = Customer::factory()->create()->id;
    $manager = member($customerId, UserRoleEnum::Manager);
    $company = Company::factory()->create(['customer_id' => $customerId]);
    $foreignCompany = Company::factory()->create();
    $status = ProjectStatus::query()->create(['name' => 'En Proceso']);

    Sanctum::actingAs($manager);

    $payload = [
        'name' => 'Gira 2026',
        'company_id' => $company->id,
        'start_date' => '2026-04-01',
        'end_date' => '2026-04-30',
        'project_status_id' => $status->id,
    ];

    postJson('/api/v1/projects', [...$payload, 'company_id' => $foreignCompany->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('company_id');

    postJson('/api/v1/projects', [...$payload, 'end_date' => '2026-03-01'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('end_date');

    $id = postJson('/api/v1/projects', $payload)
        ->assertCreated()
        ->assertJsonPath('data.status.name', 'En Proceso')
        ->assertJsonPath('data.company.id', $company->id)
        ->assertJsonPath('data.start_date', '2026-04-01')
        ->json('data.id');

    expect(Project::query()->find($id)->customer_id)->toBe($customerId);

    putJson("/api/v1/projects/{$id}", [...$payload, 'name' => 'Gira 2027'])->assertOk()->assertJsonPath('data.name', 'Gira 2027');
    deleteJson("/api/v1/projects/{$id}")->assertOk();
});

test('invoices are saved with their items', function (): void {
    $owner = User::factory()->customerOwner()->create();
    $from = Company::factory()->create(['customer_id' => $owner->customer_id]);
    $to = Company::factory()->create(['customer_id' => $owner->customer_id]);
    $foreign = Company::factory()->create();

    Sanctum::actingAs($owner);

    $payload = [
        'from_company_id' => $from->id,
        'to_company_id' => $to->id,
        'name' => 'Servicios marzo',
        'invoice_number' => 'A-0001',
        'date' => '2026-03-31',
        'total_amount' => '1000.50',
        'tax_percentage' => 21,
        'final_amount' => '1210.61',
        'payed' => false,
        'payment_due_date' => '2026-04-30',
        'items' => [
            ['name' => 'Sonido', 'quantity' => 2, 'unit_price' => '400', 'total_price' => '800'],
            ['name' => 'Luces', 'quantity' => 1, 'unit_price' => '200.50', 'total_price' => '200.50'],
        ],
    ];

    postJson('/api/v1/invoices', [...$payload, 'to_company_id' => $foreign->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('to_company_id');

    postJson('/api/v1/invoices', [...$payload, 'items' => [['name' => 'Sin precio', 'quantity' => 1]]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.unit_price', 'items.0.total_price']);

    $response = postJson('/api/v1/invoices', $payload)->assertCreated();
    $id = $response->json('data.id');
    $firstItem = $response->json('data.items.0.id');

    expect($response->json('data.total_amount'))->toBe('1000.50')
        ->and($response->json('data.discount_amount'))->toBe('0.00')
        ->and($response->json('data.from_company.id'))->toBe($from->id)
        ->and($response->json('data.items'))->toHaveCount(2)
        ->and(InvoiceItem::query()->where('invoice_id', $id)->where('customer_id', $owner->customer_id)->count())->toBe(2)
        ->and(listedIds('invoices?payed=0'))->toBe([$id])
        ->and(listedIds('invoices?payed=1'))->toBe([]);

    putJson("/api/v1/invoices/{$id}", [
        ...$payload,
        'payed' => true,
        'items' => [['id' => $firstItem, 'name' => 'Sonido y backline', 'quantity' => 3, 'unit_price' => '400', 'total_price' => '1200']],
    ])
        ->assertOk()
        ->assertJsonPath('data.payed', true)
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.id', $firstItem)
        ->assertJsonPath('data.items.0.name', 'Sonido y backline');

    deleteJson("/api/v1/invoices/{$id}")->assertOk();

    expect(Invoice::query()->whereKey($id)->exists())->toBeFalse();
});

test('payments reference companies and invoices of the same customer', function (): void {
    $owner = User::factory()->customerOwner()->create();
    $company = Company::factory()->create(['customer_id' => $owner->customer_id]);
    $invoice = Invoice::factory()->create([
        'customer_id' => $owner->customer_id,
        'from_company_id' => $company->id,
        'to_company_id' => $company->id,
    ]);
    $foreignCompany = Company::factory()->create();
    $foreignInvoice = Invoice::factory()->create([
        'customer_id' => $foreignCompany->customer_id,
        'from_company_id' => $foreignCompany->id,
        'to_company_id' => $foreignCompany->id,
    ]);

    Sanctum::actingAs($owner);

    $payload = [
        'from_company_id' => $company->id,
        'to_company_id' => $company->id,
        'invoice_id' => $invoice->id,
        'reference' => 'TRF-991',
        'amount' => '2500.75',
    ];

    postJson('/api/v1/payments', [...$payload, 'invoice_id' => $foreignInvoice->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('invoice_id');

    postJson('/api/v1/payments', ['amount' => -1])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['from_company_id', 'to_company_id', 'reference', 'amount']);

    $id = postJson('/api/v1/payments', $payload)
        ->assertCreated()
        ->assertJsonPath('data.amount', '2500.75')
        ->assertJsonPath('data.invoice.id', $invoice->id)
        ->json('data.id');

    expect(Payment::query()->find($id)->customer_id)->toBe($owner->customer_id)
        ->and(listedIds('payments?search=TRF'))->toBe([$id]);

    deleteJson("/api/v1/payments/{$id}")->assertOk();
});

test('platform admins manage customers and choose the customer of what they create', function (): void {
    $admin = User::factory()->admin()->create();
    $customer = Customer::factory()->create();

    Sanctum::actingAs($admin);

    $id = postJson('/api/v1/customers', ['name' => 'Riders Norte', 'is_active' => true])
        ->assertCreated()
        ->assertJsonPath('data.is_active', true)
        ->json('data.id');

    putJson("/api/v1/customers/{$id}", ['name' => 'Riders Norte SA', 'is_active' => false])
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    postJson('/api/v1/companies', ['name' => 'Sin cliente'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('customer_id');

    postJson('/api/v1/companies', ['name' => 'Con cliente', 'customer_id' => $customer->id])
        ->assertCreated()
        ->assertJsonPath('data.customer.id', $customer->id);

    deleteJson("/api/v1/customers/{$id}")->assertOk();
    deleteJson("/api/v1/customers/{$customer->id}")->assertConflict();
});

test('owners manage the users of their customer, with individual rates', function (): void {
    $owner = User::factory()->customerOwner()->create();
    $outsider = User::factory()->customerEmployee()->create();
    $platformAdmin = User::factory()->admin()->create(['customer_id' => $owner->customer_id]);
    $day = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);
    $guard = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::GUARD);

    DefaultRate::query()->create(['customer_id' => $owner->customer_id, 'time_tracker_item_type_id' => $guard, 'rate' => 300]);

    Sanctum::actingAs($owner);

    expect(listedIds('users'))->toBe([$owner->id]);

    getJson("/api/v1/users/{$outsider->id}")->assertNotFound();
    getJson("/api/v1/users/{$platformAdmin->id}")->assertNotFound();

    $payload = [
        'customer_id' => $outsider->customer_id,
        'name' => 'Lucía',
        'lastname' => 'Gómez',
        'email' => 'Lucia@Example.com',
        'password' => 'Una-clave-larga-2026!',
        'phone' => '11 5555 0000',
        'user_role_id' => null,
        'is_active' => true,
        'is_admin' => true,
        'fixed_daily_rate' => '1500.50',
        'individual_rates' => [$day => '9000', $guard => null],
    ];

    postJson('/api/v1/users', [...$payload, 'email' => $owner->email, 'password' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'password']);

    $response = postJson('/api/v1/users', $payload)->assertCreated();
    $created = User::query()->findOrFail($response->json('data.id'));
    $rates = collect($response->json('data.individual_rates'))->keyBy('time_tracker_item_type_id');

    expect($created->customer_id)->toBe($owner->customer_id)
        ->and($created->is_admin)->toBeFalse()
        ->and($created->email)->toBe('lucia@example.com')
        ->and($created->phone)->toBe('11 5555 0000')
        ->and(Hash::check('Una-clave-larga-2026!', $created->password))->toBeTrue()
        ->and($response->json('data'))->not->toHaveKey('password')
        ->and($rates[$day])->toMatchArray(['rate' => '9000.00', 'effective_rate' => '9000.00'])
        ->and($rates[$guard])->toMatchArray(['rate' => null, 'default_rate' => '300.00', 'effective_rate' => '300.00']);

    putJson("/api/v1/users/{$created->id}", [
        ...$payload,
        'password' => 'otra-clave-distinta-2026!',
        'user_role_id' => UserRoleEnum::Manager->value,
        'individual_rates' => [$day => null],
    ])->assertUnprocessable()->assertJsonValidationErrors('user_role_id');

    UserRole::query()->insert(['id' => UserRoleEnum::Manager->value, 'name' => 'Manager']);

    putJson("/api/v1/users/{$created->id}", [
        ...$payload,
        'password' => 'otra-clave-distinta-2026!',
        'user_role_id' => UserRoleEnum::Manager->value,
        'individual_rates' => [$day => null],
    ])->assertOk()->assertJsonPath('data.role.name', 'Manager');

    expect(UserRate::query()->where('user_id', $created->id)->count())->toBe(0)
        ->and(Hash::check('Una-clave-larga-2026!', $created->refresh()->password))->toBeTrue();

    deleteJson("/api/v1/users/{$owner->id}")->assertUnprocessable();
    deleteJson("/api/v1/users/{$created->id}")->assertOk();
});

test('an invitation is sent for the customer of the owner', function (): void {
    $owner = User::factory()->customerOwner()->create();
    $other = Customer::factory()->create();

    $emails = Mockery::mock();
    $emails->shouldReceive('send')->once()->withArgs(fn (array $payload): bool => $payload['to'] === ['nuevo@example.com']
        && str_contains($payload['html'], 'customer_id='.$owner->customer_id));
    Resend::shouldReceive('emails')->once()->andReturn($emails);

    Sanctum::actingAs($owner);

    postJson('/api/v1/users/invite', ['email' => 'Nuevo@Example.com', 'customer_id' => $other->id])
        ->assertOk()
        ->assertJsonPath('message', 'Invitación enviada');

    postJson('/api/v1/users/invite', ['email' => 'no-es-email'])->assertUnprocessable();
});

test('default rates are saved per customer and blanks remove them', function (): void {
    $owner = User::factory()->customerOwner()->create();
    $other = Customer::factory()->create();
    $day = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);
    $guard = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::GUARD);
    $expenses = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::EXPENSES);

    DefaultRate::query()->create(['customer_id' => $owner->customer_id, 'time_tracker_item_type_id' => $guard, 'rate' => 100]);
    DefaultRate::query()->create(['customer_id' => $other->id, 'time_tracker_item_type_id' => $day, 'rate' => 1]);

    Sanctum::actingAs($owner);

    $shown = collect(getJson('/api/v1/default-rates')->assertOk()->json('data.rates'))->keyBy('time_tracker_item_type_id');

    expect($shown[$guard]['rate'])->toBe('100.00')
        ->and($shown[$day]['rate'])->toBeNull()
        ->and($shown->has($expenses))->toBeFalse();

    putJson('/api/v1/default-rates', [
        'customer_id' => $other->id,
        'rates' => [$day => '5000.50', $guard => null, $expenses => '9'],
    ])->assertOk()->assertJsonPath('message', 'Tarifas por defecto guardadas');

    expect(DefaultRate::query()->where('customer_id', $owner->customer_id)->pluck('rate', 'time_tracker_item_type_id')->all())
        ->toBe([$day => '5000.50'])
        ->and(DefaultRate::query()->where('customer_id', $other->id)->value('rate'))->toBe('1.00');

    putJson('/api/v1/default-rates', ['rates' => [$day => -5]])->assertUnprocessable();
});

test('platform admins must name the customer whose default rates they save', function (): void {
    $customer = Customer::factory()->create();
    $day = TimeTrackerItemType::ensureIdFor(TimeTrackerItemTypeEnum::DAY);

    Sanctum::actingAs(User::factory()->admin()->create());

    putJson('/api/v1/default-rates', ['rates' => [$day => 10]])->assertUnprocessable()->assertJsonValidationErrors('customer_id');
    putJson('/api/v1/default-rates', ['customer_id' => $customer->id, 'rates' => [$day => 10]])->assertOk();

    getJson("/api/v1/default-rates?customer_id={$customer->id}")
        ->assertOk()
        ->assertJsonPath('data.customer_id', $customer->id);

    expect(TimeTracker::query()->count())->toBe(0);
});
