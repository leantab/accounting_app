<?php

namespace App\Filament\Resources\Projects;

use App\Enums\UserRoleEnum;
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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $navigationLabel = 'Proyectos';

    protected static ?string $recordTitleAttribute = 'Proyectos';

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->is_admin) {
            return true;
        }

        return in_array($user->user_role_id, [UserRoleEnum::Admin->value, UserRoleEnum::Owner->value, UserRoleEnum::Manager->value]);
    }

    public static function canAccess(): bool
    {
        return static::canViewAny();
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
                    ->hidden(fn() => ! Filament::auth()->user()->is_admin)
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
                    ->hidden(fn() => ! Filament::auth()->user()->is_admin),
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
            ->modifyQueryUsing(fn(Builder $query) => Filament::auth()->user()->is_admin ? $query : $query->where('customer_id', Filament::auth()->user()->customer_id))
            ->recordTitleAttribute('Proyecto')
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->hidden(fn() => ! Filament::auth()->user()->is_admin),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(20),
                TextColumn::make('start_date')
                    ->label('Fecha de inicio'),
                TextColumn::make('end_date')
                    ->label('Fecha de fin'),
                TextColumn::make('status.name')
                    ->label('Estado')
                    ->badge()
                    ->color(fn($state) => match ($state) {
                        'Borrador' => 'gray',
                        'En Proceso' => 'blue',
                        'Completado' => 'green',
                        'Cancelado' => 'red',
                    }),
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
