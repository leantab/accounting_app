<?php

namespace App\Filament\Resources\TimeTrackers\Schemas;

use App\Enums\TimeTrackerItemTypeEnum;
use App\Models\TimeTrackerItem;
use App\Models\TimeTrackerItemType;
use App\Support\Api\MobileAccess;
use Filament\Facades\Filament;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class TimeTrackerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('customer.name')
                    ->label(__('time_trackers.account'))
                    ->hidden(fn () => ! Filament::auth()->user()->is_admin),
                TextEntry::make('user.name')
                    ->label(__('common.user')),
                TextEntry::make('project.name')
                    ->label(__('common.project')),
                TextEntry::make('name')
                    ->label(__('common.name')),
                TextEntry::make('date_start')
                    ->label(__('common.start_date'))
                    ->date('d/m/Y'),
                TextEntry::make('date_end')
                    ->label(__('common.end_date'))
                    ->date('d/m/Y'),
                TextEntry::make('hours')
                    ->label(__('common.hours'))
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->label(__('common.description'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                IconEntry::make('billed')
                    ->label(__('common.billed'))
                    ->boolean(),
                TextEntry::make('amount')
                    ->label(__('common.amount'))
                    ->numeric(2, ',', '.')
                    ->placeholder('-'),
                TextEntry::make('invoice.name')
                    ->label(__('common.invoice')),
                IconEntry::make('paid')
                    ->label(__('common.paid'))
                    ->boolean(),
                TextEntry::make('payment.id')
                    ->label(__('common.payment')),
                TextEntry::make('expected_payment_date')
                    ->label(__('time_trackers.expected_payment_date'))
                    ->date('d/m/Y')
                    ->placeholder('-')
                    ->hidden(fn (): bool => MobileAccess::isEmployee(Filament::auth()->user())),
                TextEntry::make('paid_date')
                    ->label(__('time_trackers.paid_date'))
                    ->date()
                    ->placeholder('-'),
                IconEntry::make('approved')
                    ->label(__('common.approved'))
                    ->boolean(),
                TextEntry::make('approved_at')
                    ->label(__('time_trackers.approved_at'))
                    ->date('d/m/Y')
                    ->placeholder('-'),
                TextEntry::make('approvedBy.fullName')
                    ->label(__('time_trackers.approved_by')),
                TextEntry::make('created_at')
                    ->label(__('common.created_at'))
                    ->dateTime('d/m/Y')
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('common.updated_at'))
                    ->dateTime('d/m/Y')
                    ->placeholder('-'),
                RepeatableEntry::make('items')
                    ->label(__('time_trackers.items'))
                    ->columnSpanFull()
                    // ->columns(4)
                    ->table([
                        TableColumn::make(__('common.date')),
                        TableColumn::make(__('common.type')),
                        TableColumn::make(__('common.hours')),
                        TableColumn::make(__('time_trackers.start_time')),
                        TableColumn::make(__('time_trackers.end_time')),
                        TableColumn::make(__('common.description')),
                        TableColumn::make(__('common.amount')),
                    ])
                    ->schema([
                        TextEntry::make('date')
                            ->date('d/m/Y'),
                        TextEntry::make('timeTrackerItemType.name')
                            ->formatStateUsing(fn (string $state): string => TimeTrackerItemType::translate($state)),
                        TextEntry::make('hours')
                            ->state(fn (TimeTrackerItem $record): ?string => TimeTrackerItemTypeEnum::tryFrom((int) $record->time_tracker_item_type_id)?->isHourly()
                                ? $record->hours
                                : null)
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
                            ->numeric(2, ',', '.')
                            ->placeholder('-'),
                    ]),
            ]);
    }
}
