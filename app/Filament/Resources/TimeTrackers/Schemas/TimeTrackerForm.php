<?php

namespace App\Filament\Resources\TimeTrackers\Schemas;

use App\Enums\TimeTrackerItemTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
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
                    ->label('Cliente')
                    ->relationship('customer', 'name')
                    ->default(Filament::auth()->user()->customer_id)
                    ->hidden(fn () => ! Filament::auth()->user()->is_admin)
                    ->required(),
                Select::make('user_id')
                    ->label('Usuario')
                    ->options(fn () => Filament::auth()->user()->is_admin ? User::all()->pluck('name', 'id') : User::where('customer_id', Filament::auth()->user()->customer_id)->get()->pluck('name', 'id'))
                    ->default(Filament::auth()->user()->id)
                    ->hidden(fn () => Filament::auth()->user()->user_role_id == UserRoleEnum::Employee->value)
                    ->required(),
                Select::make('project_id')
                    ->label('Proyecto')
                    ->options(function () {
                        $user = Filament::auth()->user();
                        if ($user->is_admin) {
                            return Project::all()->pluck('name', 'id');
                        }

                        return Project::where('customer_id', $user->customer_id)->get()->pluck('name', 'id');
                    }),
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                DatePicker::make('date_start')
                    ->label('Fecha de inicio')
                    ->native(false)
                    ->format('Y-m-d')
                    ->displayFormat('d/m/Y')
                    ->closeOnDateSelection()
                    ->required(),
                DatePicker::make('date_end')
                    ->label('Fecha de fin')
                    ->native(false)
                    ->format('Y-m-d')
                    ->displayFormat('d/m/Y')
                    ->closeOnDateSelection()
                    ->required(),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                Toggle::make('billed')
                    ->label('Facturado')
                    ->default(false)
                    ->hidden(fn () => Filament::auth()->user()->user_role_id == UserRoleEnum::Employee->value),
                Select::make('invoice_id')
                    ->label('Factura')
                    ->options(function () {
                        $user = Filament::auth()->user();
                        if ($user->is_admin) {
                            return Invoice::all()->pluck('name', 'id');
                        }

                        return Invoice::where('customer_id', $user->customer_id)->get()->pluck('name', 'id');
                    })
                    ->hidden(fn () => Filament::auth()->user()->user_role_id == UserRoleEnum::Employee->value),
                Toggle::make('paid')
                    ->label('Pagado')
                    ->default(false)
                    ->hidden(fn () => Filament::auth()->user()->user_role_id == UserRoleEnum::Employee->value),
                Select::make('payment_id')
                    ->label('Pago')
                    ->options(fn () => Filament::auth()->user()->is_admin ? Payment::all()->pluck('reference', 'id') : Payment::where('customer_id', Filament::auth()->user()->customer_id)->get()->pluck('reference', 'id'))
                    ->hidden(fn () => Filament::auth()->user()->user_role_id == UserRoleEnum::Employee->value),
                Repeater::make('items')
                    ->label('Ítems')
                    ->addActionLabel('Agregar ítem')
                    ->relationship('items')
                    ->schema([
                        DatePicker::make('date')
                            ->label('Fecha')
                            ->format('Y-m-d')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->required(),
                        Select::make('time_tracker_item_type_id')
                            ->label('Tipo')
                            ->relationship('timeTrackerItemType', 'name')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set): void {
                                if (! in_array($get('time_tracker_item_type_id'), TimeTrackerItemTypeEnum::hourlyValues())) {
                                    $set('hours', null);
                                }
                            }),
                        TextInput::make('hours')
                            ->label('Horas')
                            ->numeric()
                            ->visible(fn (Get $get) => in_array($get('time_tracker_item_type_id'), TimeTrackerItemTypeEnum::hourlyValues()))
                            ->required(fn (Get $get) => in_array($get('time_tracker_item_type_id'), TimeTrackerItemTypeEnum::hourlyValues())),
                        self::timeSelect('time_start', 'Hora de inicio'),
                        self::timeSelect('time_end', 'Hora de fin'),
                        TextInput::make('description')
                            ->label('Descripción')
                            ->visible(fn (Get $get) => in_array($get('time_tracker_item_type_id'), [TimeTrackerItemTypeEnum::EXPENSES->value, TimeTrackerItemTypeEnum::TRAVEL->value, TimeTrackerItemTypeEnum::GUARD->value])),
                        TextInput::make('amount')
                            ->label('Monto')
                            ->numeric()
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
