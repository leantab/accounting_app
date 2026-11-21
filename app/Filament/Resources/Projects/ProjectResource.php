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
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProjectResource extends Resource
{
    protected static ?string $model = Project::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCircleStack;

    protected static ?string $recordTitleAttribute = 'name';

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
                    ->label(__('common.name'))
                    ->required()
                    ->maxLength(255),
                Select::make('customer_id')
                    ->label(__('common.customer'))
                    ->relationship('customer', 'name')
                    ->default(Filament::auth()->user()->customer_id)
                    ->hidden(fn () => ! Filament::auth()->user()->is_admin)
                    ->required(),
                Select::make('company_id')
                    ->label(__('common.company'))
                    ->relationship('company', 'name')
                    ->nullable(),
                TextInput::make('description')
                    ->label(__('common.description'))
                    ->maxLength(255),
                DatePicker::make('start_date')
                    ->label(__('common.start_date'))
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->required(),
                DatePicker::make('end_date')
                    ->label(__('common.end_date'))
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Select::make('project_status_id')
                    ->label(__('common.status'))
                    ->relationship('status', 'name')
                    ->required(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('name')
                    ->label(__('common.name')),
                TextEntry::make('customer.name')
                    ->label(__('common.customer'))
                    ->hidden(fn () => ! Filament::auth()->user()->is_admin),
                TextEntry::make('company.name')
                    ->label(__('common.company')),
                TextEntry::make('description')
                    ->label(__('common.description')),
                TextEntry::make('start_date')
                    ->label(__('common.start_date'))
                    ->date('d/m/Y'),
                TextEntry::make('end_date')
                    ->label(__('common.end_date'))
                    ->date('d/m/Y'),
                TextEntry::make('status.name')
                    ->label(__('common.status')),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => Filament::auth()->user()->is_admin ? $query : $query->where('customer_id', Filament::auth()->user()->customer_id))
            ->recordTitleAttribute('Proyecto')
            ->columns([
                TextColumn::make('name')
                    ->label(__('common.name'))
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label(__('common.customer'))
                    ->hidden(fn () => ! Filament::auth()->user()->is_admin),
                TextColumn::make('company.name')
                    ->label(__('common.company'))
                    ->searchable(),
                TextColumn::make('description')
                    ->label(__('common.description'))
                    ->limit(20),
                TextColumn::make('start_date')
                    ->label(__('common.start_date'))
                    ->date('d/m/Y'),
                TextColumn::make('end_date')
                    ->label(__('common.end_date'))
                    ->date('d/m/Y'),
                TextColumn::make('status.name')
                    ->label(__('common.status'))
                    ->badge()
                    ->color(fn ($state) => match ($state) {
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

    public static function getNavigationLabel(): string
    {
        return __('projects.plural');
    }

    public static function getModelLabel(): string
    {
        return __('common.project');
    }
}
