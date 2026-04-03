<?php

namespace App\Filament\Resources\TimeTrackers\Schemas;

use App\Enums\TimeTrackerItemTypeEnum;
use App\Enums\UserRoleEnum;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\Project;
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
                Hidden::make('user_id')
                    ->default(Filament::auth()->user()->id),
                Select::make('project_id')
                    ->label('Proyecto')
                    ->options(function () {
                        $user = Filament::auth()->user();
                        if ($user->is_admin) {
                            return Project::all()->pluck('name', 'id');
                        }

                        return Project::where('customer_id', $user->customer_id)->get()->pluck('name', 'id');
                    })
                    ->required(),
                TextInput::make('name')
                    ->required(),
                DatePicker::make('date_start')
                    ->label('Fecha de inicio')
                    ->native(false)
                    ->format('Y-m-d')
                    ->displayFormat('d/m/Y')
                    ->required(),
                DatePicker::make('date_end')
                    ->label('Fecha de fin')
                    ->native(false)
                    ->format('Y-m-d')
                    ->displayFormat('d/m/Y')
                    ->required(),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                // Select::make('invoice_id')
                //     ->label('Factura')
                //     ->options(function () {
                //         $user = Filament::auth()->user();
                //         if ($user->is_admin) {
                //             return Invoice::all()->pluck('name', 'id');
                //         }

                //         return Invoice::where('customer_id', $user->customer_id)->get()->pluck('name', 'id');
                //     })
                //     ->hidden(fn() => Filament::auth()->user()->role_id == UserRoleEnum::Employee->value),
                Toggle::make('paid')
                    ->label('Pagado')
                    ->default(false)
                    ->hidden(fn() => Filament::auth()->user()->role_id == UserRoleEnum::Employee->value),
                // Select::make('payment_id')
                //     ->label('Pago')
                //     ->options(function () {
                //         $user = Filament::auth()->user();
                //         if ($user->is_admin) {
                //             return Payment::all()->pluck('name', 'id');
                //         }

                //         return Payment::where('customer_id', $user->customer_id)->get()->pluck('name', 'id');
                //     })
                //     ->hidden(fn() => Filament::auth()->user()->role_id == UserRoleEnum::Employee->value),
                Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        Hidden::make('user_id')
                            ->default(Filament::auth()->user()->id),
                        DatePicker::make('date')
                            ->format('Y-m-d')
                            ->native(false)
                            ->displayFormat('d/m/Y')
                            ->closeOnDateSelection()
                            ->required(),
                        Select::make('time_tracker_item_type_id')
                            ->relationship('timeTrackerItemType', 'name')
                            ->required(),
                        TextInput::make('hours')
                            ->numeric()
                            ->requiredWithout('time_start', 'time_end'),
                        TimePicker::make('time_start')
                            ->format('H:i')
                            ->native(false)
                            ->seconds(false)
                            ->minutesStep(15)
                            ->displayFormat('H:i')
                            ->requiredWithout('hours'),
                        TimePicker::make('time_end')
                            ->format('H:i')
                            ->native(false)
                            ->seconds(false)
                            ->minutesStep(15)
                            ->displayFormat('H:i')
                            ->requiredWithout('hours'),
                        TextInput::make('description')
                            ->label('Descripción'),
                        TextInput::make('amount')
                            ->label('Monto')
                            ->numeric()
                            ->visible(fn(Get $get) => $get('time_tracker_item_type_id') == TimeTrackerItemTypeEnum::EXPENSES->value)
                            ->live(onBlur: true),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
