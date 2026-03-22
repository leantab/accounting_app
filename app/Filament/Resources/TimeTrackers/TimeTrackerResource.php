<?php

namespace App\Filament\Resources\TimeTrackers;

use App\Filament\Resources\TimeTrackers\Pages\ManageTimeTrackers;
use App\Models\Project;
use App\Models\TimeTracker;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TimeTrackerResource extends Resource
{
    protected static ?string $model = TimeTracker::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Select::make('customer_id')
                    ->label('Cliente')
                    ->relationship('customer', 'name')
                    ->default(Filament::auth()->user()->customer_id)
                    ->hidden(fn () => ! Filament::auth()->user()->is_admin)
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
                DatePicker::make('date_start')
                    ->label('Fecha de inicio')
                    ->format('Y-m-d')
                    ->required(),
                DatePicker::make('date_end')
                    ->label('Fecha de fin')
                    ->format('Y-m-d')
                    ->required(),
                TextInput::make('hours')
                    ->label('Horas'),
                TextInput::make('description')
                    ->label('Descripción'),
                TextInput::make('amount')
                    ->label('Monto')
                    ->disabled(true),
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
                        TextInput::make('description'),
                        // Toggle::make('is_billable'),
                        // TextInput::make('rate')->numeric(),
                        TextInput::make('amount')
                            ->numeric(),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name'),
                TextEntry::make('customer.name')
                    ->label('Cliente')
                    ->hidden(fn () => ! Filament::auth()->user()->is_admin),
                TextEntry::make('user.full_name')
                    ->label('Usuario'),
                TextEntry::make('project.name')
                    ->label('Proyecto'),
                TextEntry::make('date_start')
                    ->label('Fecha de inicio'),
                TextEntry::make('date_end')
                    ->label('Fecha de fin'),
                TextEntry::make('hours')
                    ->label('Horas'),
                TextEntry::make('description')
                    ->label('Descripción'),
                TextEntry::make('amount')
                    ->label('Monto'),
                RepeatableEntry::make('items')
                    ->schema([
                        TextEntry::make('timeTrackerItemType.name'),
                        TextEntry::make('user.name'),
                        TextEntry::make('date')->date(),
                        TextEntry::make('hours'),
                        TextEntry::make('time_start')->time(),
                        TextEntry::make('time_end')->time(),
                        TextEntry::make('description'),
                        TextEntry::make('rate'),
                        TextEntry::make('amount'),
                    ])
                    ->columns(3)
                    ->columnSpanFull(),
            ])
            ->headerActions([
                Action::make('Approve')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check')
                    ->action(function (TimeTracker $record): void {
                        $user = Filament::auth()->user();
                        $record->update([
                            'is_approved' => true,
                            'approved_by' => $user->id,
                            'approved_at' => now(),
                        ]);
                    })
                    ->requiresConfirmation()
                    ->color('warning')
                    ->modalIcon('heroicon-o-check')
                    ->modalIconColor('warning'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->hidden(fn () => ! Filament::auth()->user()->is_admin),
                TextColumn::make('user.full_name')
                    ->label('Usuario')
                    ->searchable(),
                TextColumn::make('project.name')
                    ->label('Proyecto')
                    ->searchable(),
                TextColumn::make('date_start')
                    ->label('Fecha de inicio'),
                TextColumn::make('date_end')
                    ->label('Fecha de fin'),
                TextColumn::make('hours')
                    ->label('Horas'),
                TextColumn::make('description')
                    ->label('Descripción'),
                TextColumn::make('amount')
                    ->label('Monto'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageTimeTrackers::route('/'),
        ];
    }
}
