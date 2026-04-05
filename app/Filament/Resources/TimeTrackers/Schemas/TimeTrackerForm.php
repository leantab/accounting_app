<?php

namespace App\Filament\Resources\TimeTrackers\Schemas;

use App\Enums\TimeTrackerItemTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Utilities\Get;

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
                    ->hidden(fn() => ! Filament::auth()->user()->is_admin)
                    ->required(),
                Select::make('user_id')
                    ->label('Usuario')
                    ->options(fn() => Filament::auth()->user()->is_admin ? User::all()->pluck('name', 'id') : User::where('customer_id', Filament::auth()->user()->customer_id)->get()->pluck('name', 'id'))
                    ->default(Filament::auth()->user()->id)
                    ->hidden(fn() => Filament::auth()->user()->user_role_id == UserRoleEnum::Employee->value)
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
                    ->hidden(fn() => Filament::auth()->user()->user_role_id == UserRoleEnum::Employee->value),
                Select::make('invoice_id')
                    ->label('Factura')
                    ->options(function () {
                        $user = Filament::auth()->user();
                        if ($user->is_admin) {
                            return Invoice::all()->pluck('name', 'id');
                        }

                        return Invoice::where('customer_id', $user->customer_id)->get()->pluck('name', 'id');
                    })
                    ->hidden(fn() => Filament::auth()->user()->user_role_id == UserRoleEnum::Employee->value),
                Toggle::make('paid')
                    ->label('Pagado')
                    ->default(false)
                    ->hidden(fn() => Filament::auth()->user()->user_role_id == UserRoleEnum::Employee->value),
                Select::make('payment_id')
                    ->label('Pago')
                    ->options(fn() => Filament::auth()->user()->is_admin ? Payment::all()->pluck('reference', 'id') : Payment::where('customer_id', Filament::auth()->user()->customer_id)->get()->pluck('reference', 'id'))
                    ->hidden(fn() => Filament::auth()->user()->user_role_id == UserRoleEnum::Employee->value),
                Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        DatePicker::make('date')
                            ->format('Y-m-d')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->required(),
                        Select::make('time_tracker_item_type_id')
                            ->relationship('timeTrackerItemType', 'name')
                            ->required()
                            ->live(),
                        TextInput::make('hours')
                            ->numeric()
                            ->visible(fn(Get $get) => $get('time_tracker_item_type_id') == TimeTrackerItemTypeEnum::HOURS->value)
                            ->requiredIf('time_tracker_item_type_id', TimeTrackerItemTypeEnum::HOURS->value),
                        TimePicker::make('time_start')
                            ->format('H:i')
                            ->native(false)
                            ->seconds(false)
                            ->minutesStep(15)
                            ->displayFormat('H:i')
                            ->visible(fn(Get $get) => $get('time_tracker_item_type_id') == TimeTrackerItemTypeEnum::HOURS->value),
                        TimePicker::make('time_end')
                            ->format('H:i')
                            ->native(false)
                            ->seconds(false)
                            ->minutesStep(15)
                            ->displayFormat('H:i')
                            ->visible(fn(Get $get) => $get('time_tracker_item_type_id') == TimeTrackerItemTypeEnum::HOURS->value),
                        TextInput::make('description')
                            ->label('Descripción')
                            ->visible(fn(Get $get) => in_array($get('time_tracker_item_type_id'), [TimeTrackerItemTypeEnum::EXPENSES->value, TimeTrackerItemTypeEnum::TRAVEL->value, TimeTrackerItemTypeEnum::GUARD->value])),
                        TextInput::make('amount')
                            ->label('Monto')
                            ->numeric()
                            ->visible(fn(Get $get) => in_array($get('time_tracker_item_type_id'), [TimeTrackerItemTypeEnum::EXPENSES->value, TimeTrackerItemTypeEnum::TRAVEL->value]))
                            ->live(onBlur: true),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
