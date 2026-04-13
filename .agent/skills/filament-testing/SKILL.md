---
name: filament-testing
description: "Use this skill for Filament testing in Livewire applications. Trigger when working with Filament components, building or customizing Filament resources, creating forms, tables, or other interactive elements. Covers: Filament components (resources, forms, tables, badges, tooltips, etc.)."
license: MIT
metadata:
  author: Leandro Tabajara
---

# Filament Testing Cheatsheet

## When to Apply
Activate this skill when creating Filament tests for resources, pages, testing components, or using Pest PHP to test Filament components/pages.

## 1. Authentication & Setup
```php
use App\Models\User;
use Filament\Facades\Filament;

// Autheticating in Pest
beforeEach(fn () => actingAs(User::factory()->create()));

// Multi-tenant & Panels
Filament::setTenant($team);
Filament::setCurrentPanel('admin');
Filament::bootCurrentPanel();
```

## 2. Testing Pages & Resources
```php
use function Pest\Livewire\livewire;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\CreateUser;

// List page
livewire(ListUsers::class)->assertOk()->assertCanSeeTableRecords($users);

// Create page (Form Filling & Validation)
livewire(CreateUser::class)
    ->fillForm(['name' => 'John', 'email' => 'john@test.com'])
    ->call('create')
    ->assertNotified()
    ->assertHasNoFormErrors();

// Edit / View page
livewire(EditUser::class, ['record' => $user->id])
    ->assertSchemaStateSet(['name' => $user->name])
    ->call('save');

// Relation Managers
livewire(PostsRelationManager::class, ['ownerRecord' => $user, 'pageClass' => EditUser::class])
    ->assertCanSeeTableRecords($user->posts);
```

## 3. Testing Tables
```php
use function Pest\Livewire\livewire;

livewire(ListUsers::class)
    ->assertCanSeeTableRecords($users)
    ->assertCountTableRecords(5)
    ->assertCanRenderTableColumn('name')
    ->searchTable('john') // Global search
    ->searchTableColumns(['email' => 'john@test.com'])
    ->sortTable('name', 'desc')
    ->assertTableColumnStateSet('status', 'active', record: $user)
    ->assertTableColumnVisible('created_at')
    ->filterTable('is_active', true) // Testing Filters
    ->removeTableFilters()
    ->assertTableColumnSummarySet('score', 'average', 10);
```

## 4. Testing Schemas & Forms
```php
use function Pest\Livewire\livewire;
use Filament\Forms\Components\Repeater;

livewire(CreateUser::class)
    ->assertSchemaExists('form')
    ->assertFormFieldExists('email')
    ->assertFormFieldVisible('password')
    ->assertFormFieldDisabled('role')
    ->assertHasFormErrors(['email' => 'unique'])
    ->assertSchemaComponentExists('comments-section');

// Faker for Repeaters / Builders to avoid UUID mismatches
$undoRepeaterFake = Repeater::fake();
livewire(EditPost::class)
    ->assertSchemaStateSet(['quotes' => [['content' => 'Test']]]);
$undoRepeaterFake();

// Wizards
livewire(CreatePost::class)
    ->goToNextWizardStep()
    ->goToWizardStep(2);
```

## 5. Testing Actions
```php
use function Pest\Livewire\livewire;
use Filament\Actions\Testing\TestAction;
use Filament\Actions\DeleteAction;

livewire(ListUsers::class)
    ->callAction(TestAction::make('send')->table($user)) // Table Action
    ->callAction(TestAction::make('create')->table()) // Header Action
    ->selectTableRecords($users->pluck('id')->toArray())
    ->callAction(TestAction::make('delete')->table()->bulk()); // Bulk Action

livewire(EditUser::class)
    ->assertActionExists('send')
    ->assertActionHidden('delete')
    ->assertActionHasColor('delete', 'danger')
    ->assertActionHalted('send')
    ->callAction(DeleteAction::class); // Using built-in class Action

// Action Modals & Arguments
livewire(EditInvoice::class)
    ->mountAction('send')
    ->assertMountedActionModalSee('test@example.com')
    ->callAction('send', data: ['email' => 'test@example.com'])
    ->assertHasNoFormErrors();
```

## 6. Testing Notifications
```php
use function Pest\Livewire\livewire;
use Filament\Notifications\Notification;

livewire(CreateUser::class)
    ->assertNotified()
    ->assertNotified('User created successfully')
    ->assertNotNotified('Error!');

// Direct assertion 
Notification::assertNotified();
```