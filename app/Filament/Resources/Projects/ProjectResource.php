<?php

namespace App\Filament\Resources\Projects;

use App\Filament\Resources\Projects\Pages\ManageProjects;
use App\Models\Project;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'name';

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('Todos'),
            'active' => Tab::make('Activos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('project_status_id', 1)),
            'inactive' => Tab::make('Inactivos')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('project_status_id', 2)),
        ];
    }

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
                Select::make('company_id')
                    ->label('Empresa')
                    ->relationship('company', 'name')
                    ->nullable(),
                TextInput::make('description')
                    ->label('Descripción')
                    ->maxLength(255),
                DatePicker::make('start_date')
                    ->label('Fecha de inicio')
                    ->required(),
                DatePicker::make('end_date')
                    ->label('Fecha de fin'),
                Select::make('project_status_id')
                    ->label('Estado')
                    ->relationship('status', 'name')
                    ->required(),
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
                TextEntry::make('description')
                    ->label('Descripción'),
                TextEntry::make('start_date')
                    ->label('Fecha de inicio'),
                TextEntry::make('end_date')
                    ->label('Fecha de fin'),
                TextEntry::make('status.name')
                    ->label('Estado'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Proyecto')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->hidden(fn () => ! Filament::auth()->user()->is_admin),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(20),
                TextColumn::make('start_date')
                    ->label('Fecha de inicio'),
                TextColumn::make('end_date')
                    ->label('Fecha de fin'),
                TextColumn::make('status.name')
                    ->label('Estado')
                    ->badge(),
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
            'index' => ManageProjects::route('/'),
        ];
    }
}
