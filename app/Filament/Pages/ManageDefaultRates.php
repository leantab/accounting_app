<?php

namespace App\Filament\Pages;

use App\Enums\UserRoleEnum;
use App\Filament\Money;
use App\Models\Customer;
use App\Models\DefaultRate;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Collection;

/**
 * @property-read Schema $form
 */
class ManageDefaultRates extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Tarifas por defecto';

    protected static ?string $title = 'Tarifas por defecto';

    protected string $view = 'filament.pages.manage-default-rates';

    protected ?string $subheading = 'Se aplican a todos los usuarios del cliente, salvo que el usuario tenga una tarifa propia.';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public static function canAccess(): bool
    {
        $user = Filament::auth()->user();

        if (! $user instanceof User) {
            return false;
        }

        if ($user->is_admin) {
            return true;
        }

        return $user->customer_id !== null && in_array($user->user_role_id, [
            UserRoleEnum::Owner->value,
            UserRoleEnum::Admin->value,
        ], true);
    }

    public function mount(): void
    {
        $customerId = Filament::auth()->user()?->customer_id;

        $this->form->fill([
            'customer_id' => $customerId,
            'rates' => $this->ratesForCustomer($customerId !== null ? (int) $customerId : null),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Tarifas por tipo')
                        ->description('Estos importes se usan al calcular planillas cuando el usuario no tiene una tarifa cargada para ese tipo.')
                        ->columns(2)
                        ->schema([
                            Select::make('customer_id')
                                ->label('Cliente')
                                ->options(fn (): array => Customer::query()->orderBy('name')->pluck('name', 'id')->all())
                                ->searchable()
                                ->required()
                                ->live()
                                ->columnSpanFull()
                                ->visible(fn (): bool => (bool) Filament::auth()->user()?->is_admin)
                                ->afterStateUpdated(function (Set $set, mixed $state): void {
                                    $set('rates', $this->ratesForCustomer(blank($state) ? null : (int) $state));
                                }),
                            ...$this->rateInputs(),
                        ]),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Guardar')
                                ->submit('save'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        /** @var User $user */
        $user = Filament::auth()->user();

        $customerId = $user->is_admin
            ? (int) $data['customer_id']
            : (int) $user->customer_id;

        abort_unless(Customer::query()->whereKey($customerId)->exists(), 403);

        if (! $user->is_admin) {
            abort_unless($customerId === (int) $user->customer_id, 403);
        }

        /** @var array<int|string, mixed> $rates */
        $rates = $data['rates'] ?? [];

        foreach ($this->displayedItemTypes() as $type) {
            $value = $rates[$type->id] ?? $rates[(string) $type->id] ?? null;

            if (blank($value)) {
                DefaultRate::query()
                    ->where('customer_id', $customerId)
                    ->where('time_tracker_item_type_id', $type->id)
                    ->delete();

                continue;
            }

            DefaultRate::query()->updateOrCreate(
                [
                    'customer_id' => $customerId,
                    'time_tracker_item_type_id' => $type->id,
                ],
                [
                    'rate' => $value,
                ],
            );
        }

        Notification::make()
            ->success()
            ->title('Tarifas por defecto guardadas')
            ->send();
    }

    /**
     * @return array<int, mixed>
     */
    private function ratesForCustomer(?int $customerId): array
    {
        $storedRates = $customerId === null
            ? collect()
            : DefaultRate::query()
                ->where('customer_id', $customerId)
                ->get()
                ->mapWithKeys(fn (DefaultRate $defaultRate): array => [
                    (int) $defaultRate->time_tracker_item_type_id => $defaultRate->rate,
                ]);

        $rates = [];

        foreach ($this->displayedItemTypes() as $type) {
            $rates[$type->id] = Money::format($storedRates->get($type->id));
        }

        return $rates;
    }

    /**
     * @return array<int, TextInput>
     */
    private function rateInputs(): array
    {
        return $this->displayedItemTypes()
            ->map(fn (TimeTrackerItemType $type): TextInput => Money::input('rates.'.$type->id)
                ->label($type->name))
            ->all();
    }

    /**
     * @return Collection<int, TimeTrackerItemType>
     */
    private function displayedItemTypes(): Collection
    {
        return TimeTrackerItemType::withRates();
    }
}
