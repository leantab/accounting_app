<?php

namespace App\Filament\Pages;

use App\Enums\TimeTrackerItemTypeEnum;
use App\Enums\UserRoleEnum;
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
            ...$this->ratesForCustomer($customerId !== null ? (int) $customerId : null),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([
                    Section::make('Horas y jornada')
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
                                    if (blank($state)) {
                                        $set('hourly_rate', null);
                                        $set('daily_rate', null);

                                        return;
                                    }

                                    $rates = $this->ratesForCustomer((int) $state);

                                    $set('hourly_rate', $rates['hourly_rate']);
                                    $set('daily_rate', $rates['daily_rate']);
                                }),
                            TextInput::make('hourly_rate')
                                ->label('Tarifa por hora')
                                ->helperText('Precio de una hora de trabajo (Horas).')
                                ->numeric()
                                ->minValue(0)
                                ->required(),
                            TextInput::make('daily_rate')
                                ->label('Tarifa por jornada')
                                ->helperText('Precio de un día completo (Jornada).')
                                ->numeric()
                                ->minValue(0)
                                ->required(),
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

        $this->storeRate($customerId, TimeTrackerItemTypeEnum::HOURS, (float) $data['hourly_rate']);
        $this->storeRate($customerId, TimeTrackerItemTypeEnum::DAY, (float) $data['daily_rate']);

        Notification::make()
            ->success()
            ->title('Tarifas por defecto guardadas')
            ->send();
    }

    /**
     * @return array{hourly_rate: mixed, daily_rate: mixed}
     */
    private function ratesForCustomer(?int $customerId): array
    {
        if ($customerId === null) {
            return [
                'hourly_rate' => null,
                'daily_rate' => null,
            ];
        }

        return [
            'hourly_rate' => $this->rateValue($customerId, TimeTrackerItemTypeEnum::HOURS),
            'daily_rate' => $this->rateValue($customerId, TimeTrackerItemTypeEnum::DAY),
        ];
    }

    private function rateValue(int $customerId, TimeTrackerItemTypeEnum $type): mixed
    {
        $typeId = TimeTrackerItemType::findIdFor($type);

        if ($typeId === null) {
            return null;
        }

        return DefaultRate::query()
            ->where('customer_id', $customerId)
            ->where('time_tracker_item_type_id', $typeId)
            ->value('rate');
    }

    private function storeRate(int $customerId, TimeTrackerItemTypeEnum $type, float $rate): void
    {
        DefaultRate::query()->updateOrCreate(
            [
                'customer_id' => $customerId,
                'time_tracker_item_type_id' => TimeTrackerItemType::ensureIdFor($type),
            ],
            [
                'rate' => $rate,
            ],
        );
    }
}
