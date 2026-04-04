<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
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
                TextInput::make('name')
                    ->required(),
                TextInput::make('lastname')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                TextInput::make('password')
                    ->password()
                    ->required(),
                Textarea::make('two_factor_secret')
                    ->columnSpanFull(),
                Textarea::make('two_factor_recovery_codes')
                    ->columnSpanFull(),
                DateTimePicker::make('two_factor_confirmed_at'),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('address'),
                TextInput::make('tax_id'),
                Select::make('customer_id')
                    ->label('Cliente')
                    ->relationship('customer', 'name')
                    ->hidden(fn() => ! Filament::auth()->user()->is_admin),
                Toggle::make('is_active')
                    ->label('Está activo')
                    ->required(),
                Toggle::make('is_admin')
                    ->label('Es administrador')
                    ->hidden(fn() => ! Filament::auth()->user()->is_admin)
                    ->required(),
                Section::make('Tarifas de Usuario')
                    ->schema([
                        Repeater::make('userRates')
                            ->hiddenLabel()
                            ->relationship()
                            ->schema([
                                TextInput::make('description')
                                    ->label('Descripción')
                                    ->required(),
                                TextInput::make('rate')
                                    ->label('Tarifa')
                                    ->numeric()
                                    ->required(),
                                Select::make('time_tracker_item_type_id')
                                    ->label('Tipo de item')
                                    ->relationship('timeTrackerItemType', 'name')
                                    ->required(),
                            ])
                            ->defaultItems(0)
                            ->addActionLabel('Agregar tarifa')
                            ->columns(3),
                    ]),
            ]);
    }
}
