<?php

namespace App\Filament\Resources\Payments;

use App\Enums\UserRoleEnum;
use App\Filament\Resources\Payments\Pages\ManagePayments;
use App\Models\Payment;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Models\Company;
use App\Models\Invoice;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    protected static ?string $navigationLabel = 'Pagos';

    protected static ?string $modelLabel = 'Pago';

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
                    ->label('Cliente')
                    ->relationship('customer', 'name')
                    ->default(fn() => Filament::auth()->user()?->customer_id)
                    ->visible(fn() => Filament::auth()->user()?->is_admin)
                    ->required(),
                Select::make('from_company_id')
                    ->relationship('fromCompany')
                    ->label('De')
                    ->searchable()
                    ->options(fn() => Filament::auth()->user()->customer_id ? Company::where('customer_id', Filament::auth()->user()->customer_id)->pluck('name', 'id') : Company::all()->pluck('name', 'id'))
                    ->required(),
                Select::make('to_company_id')
                    ->relationship('toCompany')
                    ->label('Para')
                    ->searchable()
                    ->options(fn() => Filament::auth()->user()->customer_id ? Company::where('customer_id', Filament::auth()->user()->customer_id)->pluck('name', 'id') : Company::all()->pluck('name', 'id'))
                    ->required(),
                Select::make('invoice_id')
                    ->relationship('invoice', 'name')
                    ->label('Factura')
                    ->searchable()
                    ->nullable(),
                TextInput::make('reference')
                    ->label('Referencia')
                    ->required(),
                TextInput::make('amount')
                    ->label('Monto')
                    ->required()
                    ->numeric(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('customer.name')
                    ->label('Cliente')
                    ->visible(fn() => Filament::auth()->user()?->is_admin),
                TextEntry::make('fromCompany.name')
                    ->label('De'),
                TextEntry::make('toCompany.name')
                    ->label('Para'),
                TextEntry::make('invoice.name')
                    ->label('Factura')
                    ->placeholder('-'),
                TextEntry::make('reference')
                    ->label('Referencia'),
                TextEntry::make('amount')
                    ->label('Monto')
                    ->numeric(),
                TextEntry::make('created_at')
                    ->label('Fecha de creación')
                    ->date('d/m/Y')
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Fecha de actualización')
                    ->date('d/m/Y')
                    ->placeholder('-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Payment')
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->sortable()
                    ->visible(fn() => Filament::auth()->user()?->is_admin),
                TextColumn::make('fromCompany.name')
                    ->label('De')
                    ->sortable(),
                TextColumn::make('toCompany.name')
                    ->label('Para')
                    ->sortable(),
                TextColumn::make('invoice.name')
                    ->label('Factura')
                    ->sortable(),
                TextColumn::make('reference')
                    ->label('Referencia')
                    ->searchable(),
                TextColumn::make('amount')
                    ->label('Monto')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label('Fecha de creación')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Fecha de actualización')
                    ->date('d/m/Y')
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
            'index' => ManagePayments::route('/'),
        ];
    }
}
