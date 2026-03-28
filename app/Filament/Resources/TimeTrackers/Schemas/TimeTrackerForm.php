<?php

namespace App\Filament\Resources\TimeTrackers\Schemas;

use App\Models\Project;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
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
                    ->format('Y-m-d')
                    ->required(),
                DatePicker::make('date_end')
                    ->label('Fecha de fin')
                    ->format('Y-m-d')
                    ->required(),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        Hidden::make('user_id')
                            ->default(Filament::auth()->user()->id),
                        DatePicker::make('date')
                            ->required(),
                        Select::make('time_tracker_item_type_id')
                            ->relationship('timeTrackerItemType', 'name')
                            ->required(),
                        TextInput::make('hours')
                            ->numeric()
                            ->requiredWithout('time_start', 'time_end'),
                        TimePicker::make('time_start')
                            ->format('H:i')
                            ->requiredWithout('hours'),
                        TimePicker::make('time_end')
                            ->format('H:i')
                            ->requiredWithout('hours'),
                        TextInput::make('description')
                            ->label('Descripción'),
                        TextInput::make('amount')
                            ->label('Monto')
                            ->numeric()
                            ->visible(fn($item) => $item->timeTrackerItemType->name === 'Consultoría'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }
}
