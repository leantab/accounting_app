<?php

use App\Enums\CheckInTypeEnum;
use App\Enums\TimeTrackerItemTypeEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Pages\ManageDefaultRates;
use App\Filament\Pages\RegisterCheckIn;
use App\Filament\Resources\CheckIns\CheckInResource;
use App\Filament\Resources\TimeTrackers\TimeTrackerResource;
use App\Mail\TimeTrackerApprovedMail;
use App\Models\Customer;
use App\Models\TimeTracker;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use function Pest\Laravel\actingAs;
use function Pest\Livewire\livewire;

uses(TestCase::class, RefreshDatabase::class);

it('shows the application in Spanish by default and falls back to English', function (): void {
    expect(config('app.locale'))->toBe('es')
        ->and(config('app.fallback_locale'))->toBe('en')
        ->and(app()->getLocale())->toBe('es');
});

it('translates labels, navigation and enums when the locale changes', function (): void {
    expect(CheckInResource::getNavigationLabel())->toBe('Asistencia')
        ->and(TimeTrackerResource::getPluralModelLabel())->toBe('Planillas de horas')
        ->and(CheckInTypeEnum::In->label())->toBe('Entrada');

    app()->setLocale('en');

    expect(CheckInResource::getNavigationLabel())->toBe('Attendance')
        ->and(TimeTrackerResource::getPluralModelLabel())->toBe('Time sheets')
        ->and(CheckInTypeEnum::In->label())->toBe('Check-in');
});

it('renders a page title in the current language', function (): void {
    actingAs(User::factory()->admin()->create());

    expect((string) (new ManageDefaultRates)->getTitle())->toBe('Tarifas por defecto');

    app()->setLocale('en');

    expect((string) (new ManageDefaultRates)->getTitle())->toBe('Default rates');
});

it('translates item type names for display but keeps the canonical name stored', function (): void {
    expect(TimeTrackerItemTypeEnum::DAY->label())->toBe('Jornal')
        ->and(TimeTrackerItemType::translate('Jornal'))->toBe('Jornal')
        ->and(TimeTrackerItemType::translate('Tipo desconocido'))->toBe('Tipo desconocido');

    app()->setLocale('en');

    expect(TimeTrackerItemTypeEnum::DAY->label())->toBe('Jornal')
        ->and(TimeTrackerItemType::translate('Jornal'))->toBe('Day shift')
        ->and(TimeTrackerItemType::translate('Hora extra Rigging MAIN'))->toBe('Extra hour Rigging MAIN')
        ->and(TimeTrackerItemType::translateMany(['Gastos', 'Viáticos']))->toBe('Expenses, Travel allowance')
        ->and(TimeTrackerItemType::translate('Tipo desconocido'))->toBe('Tipo desconocido');
});

it('lists the rate pages with item type names in the current language', function (): void {
    $admin = User::factory()->admin()->create();

    foreach (TimeTrackerItemTypeEnum::cases() as $type) {
        TimeTrackerItemType::query()->insertOrIgnore(['id' => $type->value, 'name' => $type->label()]);
    }

    actingAs($admin);
    app()->setLocale('en');

    $labels = TimeTrackerItemType::withRates()->map(fn (TimeTrackerItemType $type): string => $type->displayName());

    expect($labels)->toContain('Day shift MAIN', 'Console', 'Warehouse')
        ->and($labels)->not->toContain('Jornal MAIN');
});

it('builds the approval email in the current language', function (): void {
    $tracker = TimeTracker::factory()->create(['name' => 'Obra Norte', 'amount' => 12345.5]);

    $spanish = new TimeTrackerApprovedMail($tracker);

    expect($spanish->envelope()->subject)->toBe('Tu planilla "Obra Norte" fue aprobada');
    $spanish->assertSeeInHtml('Ya fue aprobada tu planilla');
    $spanish->assertSeeInHtml('$ 12.345,50');

    app()->setLocale('en');

    $english = new TimeTrackerApprovedMail($tracker);

    expect($english->envelope()->subject)->toBe('Your time sheet "Obra Norte" was approved');
    $english->assertSeeInHtml('Your time sheet');
    $english->assertSeeInHtml('was approved');
    $english->assertSeeInHtml('$ 12.345,50');
});

it('translates the starter kit texts into Spanish through the JSON file', function (): void {
    expect(__('Log in'))->toBe('Iniciar sesión')
        ->and(__("Don't have an account?"))->toBe('¿No tenés una cuenta?');

    app()->setLocale('en');

    expect(__('Log in'))->toBe('Log in');
});

it('renders the check in page texts in the current language', function (): void {
    $customer = Customer::factory()->create();
    $employee = User::factory()->create(['customer_id' => $customer->id, 'user_role_id' => UserRoleEnum::Employee->value]);

    actingAs($employee);

    livewire(RegisterCheckIn::class)
        ->assertSee('Registrar entrada')
        ->assertSee('Todavía no registró ninguna asistencia.');

    app()->setLocale('en');

    livewire(RegisterCheckIn::class)
        ->assertSee('Record check-in')
        ->assertSee('You have not recorded any attendance yet.');
});

it('serves the landing page in the current language', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSee('lang="es"', false)
        ->assertSee('Gestión Inteligente para tu Empresa')
        ->assertSee('Planes para cada etapa')
        ->assertSee('Ingresar')
        ->assertDontSee('Plans for every stage');

    app()->setLocale('en');

    $this->get('/')
        ->assertOk()
        ->assertSee('lang="en"', false)
        ->assertSee('Smart Management for Your Business')
        ->assertSee('Plans for every stage')
        ->assertSee('Pricing')
        ->assertSee('USD 79')
        ->assertDontSee('Planes para cada etapa');
});

it('keeps the application name and the highlighted word in the landing title', function (): void {
    $this->get('/')
        ->assertSee(config('app.name').': Gestión', false)
        ->assertSee('<span class="text-primary italic">Inteligente</span>', false);

    app()->setLocale('en');

    $this->get('/')
        ->assertSee('<span class="text-primary italic">Smart</span> management for your business', false);
});
