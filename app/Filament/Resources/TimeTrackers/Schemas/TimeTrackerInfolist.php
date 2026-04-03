<?php

namespace App\Filament\Resources\TimeTrackers\Schemas;

use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TimeTrackerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('customer.name')
                    ->label('Cuenta')
                    ->hidden(fn() => ! Filament::auth()->user()->is_admin),
                TextEntry::make('user.name')
                    ->label('Usuario'),
                TextEntry::make('project.name')
                    ->label('Proyecto'),
                TextEntry::make('name')
                    ->label('Nombre'),
                TextEntry::make('date_start')
                    ->label('Fecha de inicio')
                    ->date('d/m/Y'),
                TextEntry::make('date_end')
                    ->label('Fecha de fin')
                    ->date('d/m/Y'),
                TextEntry::make('hours')
                    ->label('Horas')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->label('Descripción')
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('billed')
                    ->label('Facturado')
                    ->boolean(),
                TextEntry::make('amount')
                    ->label('Monto')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('invoice.name')
                    ->label('Factura'),
                IconEntry::make('paid')
                    ->label('Pagado')
                    ->boolean(),
                TextEntry::make('payment.id')
                    ->label('Payment'),
                TextEntry::make('paid_date')
                    ->label('Fecha de pago')
                    ->date()
                    ->placeholder('-'),
                IconEntry::make('approved')
                    ->label('Aprobado')
                    ->boolean(),
                TextEntry::make('approved_at')
                    ->label('Fecha de aprobación')
                    ->date('d/m/Y')
                    ->placeholder('-'),
                TextEntry::make('approvedBy.fullName')
                    ->label('Aprobado por'),
                TextEntry::make('created_at')
                    ->label('Fecha de creación')
                    ->dateTime('d/m/Y')
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Fecha de actualización')
                    ->dateTime('d/m/Y')
                    ->placeholder('-'),
                RepeatableEntry::make('items')
                    ->label('Items')
                    ->columnSpanFull()
                    // ->columns(4)
                    ->table([
                        TableColumn::make('Fecha'),
                        TableColumn::make('Tipo'),
                        TableColumn::make('Horas'),
                        TableColumn::make('Hora de inicio'),
                        TableColumn::make('Hora de fin'),
                        TableColumn::make('Descripción'),
                        TableColumn::make('Monto'),
                    ])
                    ->schema([
                        TextEntry::make('date')
                            ->date('d/m/Y'),
                        TextEntry::make('timeTrackerItemType.name'),
                        TextEntry::make('hours')
                            ->numeric()
                            ->placeholder('-'),
                        TextEntry::make('time_start')
                            ->time('H:i')
                            ->placeholder('-'),
                        TextEntry::make('time_end')
                            ->time('H:i')
                            ->placeholder('-'),
                        TextEntry::make('description')
                            ->placeholder('-'),
                        TextEntry::make('amount')
                            ->numeric()
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
