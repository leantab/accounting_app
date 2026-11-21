<?php

namespace App\Filament\Resources\TimeTrackers\Schemas;

use App\Enums\TimeTrackerItemTypeEnum;
use App\Enums\UserRoleEnum;
use App\Filament\Money;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\TimeTrackerItemType;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class TimeTrackerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->label(__('common.customer'))
                    ->relationship('customer', 'name')
                    ->default(Filament::auth()->user()->customer_id)
                    ->hidden(fn () => ! Filament::auth()->user()->is_admin)
                    ->required(),
                Select::make('user_id')
                    ->label(__('common.user'))
                    ->options(fn () => Filament::auth()->user()->is_admin ? User::all()->pluck('name', 'id') : User::where('customer_id', Filament::auth()->user()->customer_id)->get()->pluck('name', 'id'))
                    ->default(Filament::auth()->user()->id)
                    ->hidden(fn () => Filament::auth()->user()->user_role_id == UserRoleEnum::Employee->value)
                    ->dehydratedWhenHidden()
                    ->dehydrateStateUsing(fn ($state) => Filament::auth()->user()->user_role_id == UserRoleEnum::Employee->value
                        ? Filament::auth()->id()
                        : $state)
                    ->required(),
                Select::make('project_id')
                    ->label(__('common.project'))
                    ->options(function () {
                        $user = Filament::auth()->user();
                        if ($user->is_admin) {
                            return Project::all()->pluck('name', 'id');
                        }

                        return Project::where('customer_id', $user->customer_id)->get()->pluck('name', 'id');
                    }),
                TextInput::make('name')
                    ->label(__('common.name'))
                    ->required(),
                DatePicker::make('date_start')
                    ->label(__('common.start_date'))
                    ->native(false)
                    ->format('Y-m-d')
                    ->displayFormat('d/m/Y')
                    ->closeOnDateSelection()
                    ->required(),
                DatePicker::make('date_end')
                    ->label(__('common.end_date'))
                    ->native(false)
                    ->format('Y-m-d')
                    ->displayFormat('d/m/Y')
                    ->closeOnDateSelection()
                    ->required(),
                Textarea::make('description')
                    ->label(__('common.description'))
                    ->columnSpanFull(),
                Toggle::make('billed')
                    ->label(__('common.billed'))
                    ->default(false)
                    ->hidden(fn () => Filament::auth()->user()->user_role_id == UserRoleEnum::Employee->value),
                Select::make('invoice_id')
                    ->label(__('common.invoice'))
                    ->options(function () {
                        $user = Filament::auth()->user();
                        if ($user->is_admin) {
                            return Invoice::all()->pluck('name', 'id');
                        }

                        return Invoice::where('customer_id', $user->customer_id)->get()->pluck('name', 'id');
                    })
                    ->hidden(fn () => Filament::auth()->user()->user_role_id == UserRoleEnum::Employee->value),
                Toggle::make('paid')
                    ->label(__('common.paid'))
                    ->default(false)
                    ->hidden(fn () => Filament::auth()->user()->user_role_id == UserRoleEnum::Employee->value),
                Select::make('payment_id')
                    ->label(__('common.payment'))
                    ->options(fn () => Filament::auth()->user()->is_admin ? Payment::all()->pluck('reference', 'id') : Payment::where('customer_id', Filament::auth()->user()->customer_id)->get()->pluck('reference', 'id'))
                    ->hidden(fn () => Filament::auth()->user()->user_role_id == UserRoleEnum::Employee->value),
                DatePicker::make('expected_payment_date')
                    ->label(__('time_trackers.expected_payment_date'))
                    ->native(false)
                    ->format('Y-m-d')
                    ->displayFormat('d/m/Y')
                    ->closeOnDateSelection()
                    ->hidden(fn () => Filament::auth()->user()->user_role_id == UserRoleEnum::Employee->value),
                Repeater::make('items')
                    ->label(__('time_trackers.items'))
                    ->addActionLabel(__('time_trackers.add_item'))
                    ->relationship('items')
                    ->schema([
                        DatePicker::make('date')
                            ->label(__('common.date'))
                            ->format('Y-m-d')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->required(),
                        Select::make('time_tracker_item_type_id')
                            ->label(__('common.type'))
                            ->relationship('timeTrackerItemType', 'name')
                            ->getOptionLabelFromRecordUsing(fn (TimeTrackerItemType $record): string => $record->displayName())
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                if (! in_array($get('time_tracker_item_type_id'), TimeTrackerItemTypeEnum::hourlyValues())) {
                                    $set('hours', null);
                                }
                            }),
                        TextInput::make('hours')
                            ->label(__('common.hours'))
                            ->numeric()
                            ->visible(fn (Get $get) => in_array($get('time_tracker_item_type_id'), TimeTrackerItemTypeEnum::hourlyValues()))
                            ->required(fn (Get $get) => in_array($get('time_tracker_item_type_id'), TimeTrackerItemTypeEnum::hourlyValues())),
                        self::timeSelect('time_start', __('time_trackers.start_time')),
                        self::timeSelect('time_end', __('time_trackers.end_time')),
                        TextInput::make('description')
                            ->label(__('common.description'))
                            ->visible(fn (Get $get) => in_array($get('time_tracker_item_type_id'), [TimeTrackerItemTypeEnum::EXPENSES->value, TimeTrackerItemTypeEnum::TRAVEL->value, TimeTrackerItemTypeEnum::GUARD->value])),
                        Money::input('amount')
                            ->label(__('common.amount'))
                            ->visible(fn (Get $get) => in_array($get('time_tracker_item_type_id'), [TimeTrackerItemTypeEnum::EXPENSES->value, TimeTrackerItemTypeEnum::TRAVEL->value]))
                            ->live(onBlur: true),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    private static function timeSelect(string $name, string $label): Select
    {
        return Select::make($name)
            ->label($label)
            ->options(self::timeOptions())
            ->searchable()
            ->placeholder('--:--')
            ->formatStateUsing(fn ($state): ?string => filled($state) ? Carbon::parse($state)->format('H:i') : null);
    }

    /**
     * @return array<string, string>
     */
    private static function timeOptions(): array
    {
        $options = [];

        for ($minutes = 0; $minutes < 24 * 60; $minutes += 30) {
            $time = sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
            $options[$time] = $time;
        }

        return $options;
    }
}
