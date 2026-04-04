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

    protected static ?string $recordTitleAttribute = 'Cuentas/Clientes';

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->is_admin) {
            return true;
        }

        return $user->customer_id != null && in_array($user->role_id, [UserRoleEnum::Owner->value, UserRoleEnum::Admin->value]);
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
                    ->label('Customer')
                    ->searchable()
                    ->options(Customer::all()->pluck('name', 'id'))
                    ->default(fn() => Filament::auth()->user()?->currentCustomerId())
                    ->visible(fn() => Filament::auth()->user()?->isAdmin()),
                MorphToSelect::make('companyable')
                    ->types([
                        MorphToSelect\Type::make(Customer::class)
                            ->titleAttribute('name'),
                        MorphToSelect\Type::make(User::class)
                            ->titleAttribute('email'),
                    ])
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required(),
                Textarea::make('description')
                    ->columnSpanFull(),
                TextInput::make('phone')
                    ->tel(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email(),
                TextInput::make('address'),
                TextInput::make('tax_id'),
                Toggle::make('is_tax_retained')
                    ->label('Tax Retained')
                    ->default(false),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('companyable_type')
                    ->label('Related Type')
                    ->formatStateUsing(fn($state) => class_basename($state)),
                TextEntry::make('companyable.name')
                    ->label('Related Entity'),
                TextEntry::make('name'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('phone')
                    ->placeholder('-'),
                TextEntry::make('email')
                    ->label('Email address')
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->placeholder('-'),
                TextEntry::make('tax_id')
                    ->placeholder('-'),
                IconEntry::make('is_tax_retained')
                    ->boolean()
                    ->label('Tax Retained'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Company')
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->visible(fn() => Filament::auth()->user()?->isAdmin()),
                TextColumn::make('companyable_type')
                    ->label('Related Type')
                    ->formatStateUsing(fn($state) => class_basename($state))
                    ->sortable(),
                TextColumn::make('companyable.name')
                    ->label('Related Entity')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('Email address')
                    ->searchable(),
                TextColumn::make('address')
                    ->searchable(),
                TextColumn::make('tax_id')
                    ->label('Tax ID')
                    ->searchable(),
                IconColumn::make('is_tax_retained')
                    ->boolean()
                    ->label('Tax Retained'),
                TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated At')
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
