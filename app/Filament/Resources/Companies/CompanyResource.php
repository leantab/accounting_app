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
                    ->label(__('common.customer'))
                    ->searchable()
                    ->options(Customer::all()->pluck('name', 'id'))
                    ->required()
                    ->default(fn () => Filament::auth()->user()?->currentCustomerId())
                    ->visible(fn () => Filament::auth()->user()?->isAdmin()),
                /*MorphToSelect::make('companyable')
                    ->label(__('companies.related_with'))
                    ->types([
                        MorphToSelect\Type::make(Customer::class)
                            ->label(__('common.customer'))
                            ->titleAttribute('name'),
                        MorphToSelect\Type::make(User::class)
                            ->label(__('common.user'))
                            ->titleAttribute('email'),
                    ])
                    ->searchable()
                    ->preload(),*/
                TextInput::make('name')
                    ->label(__('common.name'))
                    ->required(),
                Textarea::make('description')
                    ->label(__('common.description'))
                    ->columnSpanFull(),
                TextInput::make('phone')
                    ->label(__('common.phone'))
                    ->tel(),
                TextInput::make('email')
                    ->label(__('common.email'))
                    ->email(),
                TextInput::make('address')
                    ->label(__('common.address')),
                TextInput::make('tax_id')
                    ->label(__('common.tax_id')),
                Toggle::make('is_tax_retained')
                    ->label(__('companies.retains_taxes'))
                    ->default(false),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                /*TextEntry::make('companyable_type')
                    ->label(__('companies.related_type'))
                    ->formatStateUsing(fn ($state) => class_basename($state)),
                TextEntry::make('companyable.name')
                    ->label(__('companies.related_entity')),*/
                TextEntry::make('name'),
                TextEntry::make('description')
                    ->label(__('common.description'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('phone')
                    ->label(__('common.phone'))
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label(__('common.email'))
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->label(__('common.address'))
                    ->placeholder('-'),
                TextEntry::make('tax_id')
                    ->label(__('common.tax_id'))
                    ->placeholder('-'),
                IconEntry::make('is_tax_retained')
                    ->boolean()
                    ->label(__('companies.retains_taxes')),
                TextEntry::make('created_at')
                    ->label(__('common.created'))
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('common.updated'))
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
                    ->label(__('common.customer'))
                    ->sortable()
                    ->visible(fn () => Filament::auth()->user()?->isAdmin()),
                /*TextColumn::make('companyable_type')
                    ->label(__('companies.related_type'))
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->sortable(),
                TextColumn::make('companyable.name')
                    ->label(__('companies.related_entity'))
                    ->searchable()
                    ->sortable(),*/
                TextColumn::make('name')
                    ->label(__('common.name'))
                    ->searchable(),
                TextColumn::make('phone')
                    ->label(__('common.phone'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('common.email'))
                    ->searchable(),
                TextColumn::make('address')
                    ->label(__('common.address'))
                    ->searchable(),
                TextColumn::make('tax_id')
                    ->label(__('common.tax_id'))
                    ->label(__('common.tax_id'))
                    ->searchable(),
                IconColumn::make('is_tax_retained')
                    ->boolean()
                    ->label(__('companies.retains_taxes')),
                TextColumn::make('created_at')
                    ->label(__('common.created'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('common.updated'))
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

    public static function getNavigationLabel(): string
    {
        return __('companies.plural');
    }

    public static function getModelLabel(): string
    {
        return __('common.company');
    }

    public static function getPluralModelLabel(): string
    {
        return __('companies.plural');
    }
}
