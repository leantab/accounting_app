<?php

namespace App\Filament\Resources\Companies;

use App\Enums\UserRoleEnum;
use App\Filament\Resources\Companies\Pages\ManageCompanies;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\MorphToSelect;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static ?string $navigationLabel = 'Empresas';

    protected static ?string $modelLabel = 'Empresa';

    protected static ?string $pluralModelLabel = 'Empresas';

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

        return $user->customer_id != null && in_array($user->user_role_id, [UserRoleEnum::Owner->value, UserRoleEnum::Admin->value]);
    }

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->relationship('customer')
                    ->label('Cliente')
                    ->searchable()
                    ->options(Customer::all()->pluck('name', 'id'))
                    ->required()
                    ->default(fn () => Filament::auth()->user()?->currentCustomerId())
                    ->visible(fn () => Filament::auth()->user()?->isAdmin()),
                /*MorphToSelect::make('companyable')
                    ->label('Relacionado con')
                    ->types([
                        MorphToSelect\Type::make(Customer::class)
                            ->label('Cliente')
                            ->titleAttribute('name'),
                        MorphToSelect\Type::make(User::class)
                            ->label('Usuario')
                            ->titleAttribute('email'),
                    ])
                    ->searchable()
                    ->preload(),*/
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                Textarea::make('description')
                    ->label('Descripción')
                    ->columnSpanFull(),
                TextInput::make('phone')
                    ->label('Teléfono')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email')
                    ->email(),
                TextInput::make('address')
                    ->label('Dirección'),
                TextInput::make('tax_id')
                    ->label('ID fiscal'),
                Toggle::make('is_tax_retained')
                    ->label('Retiene impuestos')
                    ->default(false),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                /*TextEntry::make('companyable_type')
                    ->label('Tipo relacionado')
                    ->formatStateUsing(fn ($state) => class_basename($state)),
                TextEntry::make('companyable.name')
                    ->label('Entidad relacionada'),*/
                TextEntry::make('name'),
                TextEntry::make('description')
                    ->label('Descripción')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('phone')
                    ->label('Teléfono')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email')
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->label('Dirección')
                    ->placeholder('-'),
                TextEntry::make('tax_id')
                    ->label('ID fiscal')
                    ->placeholder('-'),
                IconEntry::make('is_tax_retained')
                    ->boolean()
                    ->label('Retiene impuestos'),
                TextEntry::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->sortable()
                    ->visible(fn () => Filament::auth()->user()?->isAdmin()),
                /*TextColumn::make('companyable_type')
                    ->label('Tipo relacionado')
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->sortable(),
                TextColumn::make('companyable.name')
                    ->label('Entidad relacionada')
                    ->searchable()
                    ->sortable(),*/
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                TextColumn::make('address')
                    ->label('Dirección')
                    ->searchable(),
                TextColumn::make('tax_id')
                    ->label('ID fiscal')
                    ->label('ID fiscal')
                    ->searchable(),
                IconColumn::make('is_tax_retained')
                    ->boolean()
                    ->label('Retiene impuestos'),
                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Actualizado')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            'index' => ManageCompanies::route('/'),
        ];
    }
}
