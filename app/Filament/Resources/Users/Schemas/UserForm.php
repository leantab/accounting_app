<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Filament\Money;
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
                    ->label(__('common.customer'))
                    ->relationship('customer', 'name')
                    ->hidden(fn () => ! Filament::auth()->user()->is_admin),
                TextInput::make('name')
                    ->label(__('common.name'))
                    ->required(),
                TextInput::make('lastname')
                    ->label(__('common.last_name'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('common.email'))
                    ->email()
                    ->required(),
                TextInput::make('phone')
                    ->label(__('common.phone'))
                    ->tel(),
                TextInput::make('address')
                    ->label(__('common.address')),
                TextInput::make('tax_id')
                    ->label(__('common.tax_id')),
                Select::make('role_id')
                    ->label(__('common.role'))
                    ->options(UserRole::all()->pluck('name', 'id')),
                Toggle::make('is_active')
                    ->label(__('common.is_active'))
                    ->required(),
                Toggle::make('is_admin')
                    ->label(__('common.is_admin'))
                    ->hidden(fn () => ! Filament::auth()->user()->is_admin)
                    ->required(),
                Section::make(__('users.fixed_rate'))
                    ->description(__('users.fixed_rate_help'))
                    ->columnSpanFull()
                    ->columns(2)
                    ->schema([
                        Money::input('fixed_daily_rate')
                            ->label(__('users.fixed_daily_rate')),
                        Money::input('fixed_monthly_rate')
                            ->label(__('users.fixed_monthly_rate')),
                    ]),
                Section::make(__('users.individual_rates'))
                    ->description(__('users.individual_rates_help'))
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
            ->map(fn (TimeTrackerItemType $type): TextInput => Money::input((string) $type->id)
                ->label($type->displayName())
                ->placeholder(__('common.default'))
                ->afterStateHydrated(function (TextInput $component, ?User $record) use ($type): void {
                    $rate = $record?->userRates->firstWhere('time_tracker_item_type_id', $type->id)?->rate;

                    $component->state(Money::format($rate));
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
        foreach ($rates as $typeId => $typedRate) {
            $rate = Money::parse($typedRate);

            $query = UserRate::query()
                ->where('user_id', $user->id)
                ->where('time_tracker_item_type_id', (int) $typeId);

            if ($rate === null) {
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
