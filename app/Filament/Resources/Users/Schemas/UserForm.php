<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\TimeTrackerItemType;
use App\Models\User;
use App\Models\UserRate;
use App\Models\UserRole;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->label('Cliente')
                    ->relationship('customer', 'name')
                    ->hidden(fn () => ! Filament::auth()->user()->is_admin),
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                TextInput::make('lastname')
                    ->label('Apellido')
                    ->required(),
                TextInput::make('email')
                    ->label('Email')
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel(),
                TextInput::make('address')
                    ->label('Dirección'),
                TextInput::make('tax_id')
                    ->label('ID fiscal'),
                Select::make('role_id')
                    ->label('Rol')
                    ->options(UserRole::all()->pluck('name', 'id')),
                Toggle::make('is_active')
                    ->label('Está activo')
                    ->required(),
                Toggle::make('is_admin')
                    ->label('Es administrador')
                    ->hidden(fn () => ! Filament::auth()->user()->is_admin)
                    ->required(),
                Section::make('Tarifa fija')
                    ->description('Opcional. Para usuarios con sueldo fijo: por jornada, por mes, o ambas.')
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        TextInput::make('fixed_daily_rate')
                            ->label('Tarifa fija por jornada')
                            ->numeric()
                            ->minValue(0)
                            ->nullable(),
                        TextInput::make('fixed_monthly_rate')
                            ->label('Tarifa fija mensual')
                            ->numeric()
                            ->minValue(0)
                            ->nullable(),
                    ]),
                Section::make('Tarifas individuales')
                    ->description('Reemplazan la tarifa por defecto del mismo tipo. Si se deja vacío, se usa la tarifa por defecto.')
                    ->columnSpanFull()
                    ->columns(3)
                    ->schema([
                        Group::make(self::individualRateInputs())
                            ->statePath('individualRates')
                            ->columnSpanFull()
                            ->columns(3)
                            ->saveRelationshipsUsing(fn (Group $component, User $record) => self::saveIndividualRates($record, $component->getState())),
                    ]),
            ]);
    }

    /**
     * @return array<int, TextInput>
     */
    private static function individualRateInputs(): array
    {
        return TimeTrackerItemType::withRates()
            ->map(fn (TimeTrackerItemType $type): TextInput => TextInput::make((string) $type->id)
                ->label($type->name)
                ->numeric()
                ->minValue(0)
                ->placeholder('Por defecto')
                ->afterStateHydrated(function (TextInput $component, ?User $record) use ($type): void {
                    $rate = $record?->userRates->firstWhere('time_tracker_item_type_id', $type->id)?->rate;

                    $component->state($rate === null ? null : (float) $rate);
                }))
            ->all();
    }

    /**
     * Blank inputs fall back to the default rate, so they remove any custom rate.
     *
     * @param  array<string, mixed>  $rates
     */
    private static function saveIndividualRates(User $user, array $rates): void
    {
        foreach ($rates as $typeId => $rate) {
            $query = UserRate::query()
                ->where('user_id', $user->id)
                ->where('time_tracker_item_type_id', (int) $typeId);

            if (! filled($rate)) {
                $query->delete();

                continue;
            }

            $query->exists()
                ? $query->update(['rate' => $rate])
                : UserRate::query()->create([
                    'customer_id' => $user->customer_id,
                    'user_id' => $user->id,
                    'time_tracker_item_type_id' => (int) $typeId,
                    'rate' => $rate,
                ]);
        }
    }
}
