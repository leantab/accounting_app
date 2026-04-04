<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Facades\Filament;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class UserInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('customer.name')
                    ->label('Cliente')
                    ->visible(fn() => Filament::auth()->user()->is_admin),
                TextEntry::make('name')
                    ->label('Nombre'),
                TextEntry::make('lastname')
                    ->label('Apellido'),
                TextEntry::make('role.name')
                    ->label('Rol'),
                TextEntry::make('email')
                    ->label('Email'),
                TextEntry::make('phone')
                    ->label('Teléfono')
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->label('Dirección')
                    ->placeholder('-'),
                TextEntry::make('tax_id')
                    ->label('CUIL/CUIT')
                    ->placeholder('-'),
                IconEntry::make('is_active')
                    ->label('Activo')
                    ->boolean(),
                IconEntry::make('is_admin')
                    ->label('Admin')
                    ->visible(fn() => Filament::auth()->user()->is_admin)
                    ->boolean(),
                TextEntry::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('-'),
                RepeatableEntry::make('userRates')
                    ->label('Tarifas de Usuario')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('description')
                            ->label('Descripción'),
                        TextEntry::make('rate')
                            ->label('Tarifa'),
                        TextEntry::make('timeTrackerItemType.name')
                            ->label('Tipo de item'),
                    ]),
            ]);
    }
}
