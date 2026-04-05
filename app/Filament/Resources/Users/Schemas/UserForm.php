<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\UserRole;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                    ->hidden(fn() => ! Filament::auth()->user()->is_admin),
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
                    ->hidden(fn() => ! Filament::auth()->user()->is_admin)
                    ->required(),
                Section::make('Tarifas de Usuario')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('userRates')
                            ->label('Tarifas de Usuario')
                            ->collapsible()
                            ->columnSpanFull()
                            ->relationship()
                            ->schema([
                                Select::make('time_tracker_item_type_id')
                                    ->label('Tipo de item')
                                    ->relationship('timeTrackerItemType', 'name')
                                    ->required(),
                                TextInput::make('description')
                                    ->label('Descripción'),
                                TextInput::make('rate')
                                    ->label('Tarifa')
                                    ->numeric()
                                    ->required(),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Agregar tarifa')
                            ->columns(3),
                    ]),
            ]);
    }
}
