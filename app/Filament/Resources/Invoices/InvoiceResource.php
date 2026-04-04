<?php

namespace App\Filament\Resources\Invoices;

use App\Actions\ProcessPDFInvoice;
use App\Enums\UserRoleEnum;
use App\Filament\Resources\Invoices\Pages\ManageInvoices;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Facturas';

    protected static ?string $recordTitleAttribute = 'Facturas';

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->is_admin) {
            return true;
        }

        return in_array($user->role_id, [UserRoleEnum::Admin->value, UserRoleEnum::Owner->value]);
    }

    public static function canAccess(): bool
    {
        return static::canViewAny();
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('customer_id', Filament::auth()->user()->customer_id)->where('payed', false)->count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('customer_id')
                    ->label('Customer')
                    ->searchable()
                    ->options(Customer::all()->pluck('name', 'id'))
                    ->default(fn() => Filament::auth()->user()?->customer_id)
                    ->visible(fn() => Filament::auth()->user()?->is_admin),
                Select::make('from_company_id')
                    ->relationship('fromCompany')
                    ->label('De')
                    ->searchable()
                    ->options(fn() => Filament::auth()->user()->customer_id ? Company::where('customer_id', Filament::auth()->user()->customer_id)->pluck('name', 'id') : Company::all()->pluck('name', 'id')),
                Select::make('to_company_id')
                    ->relationship('toCompany')
                    ->label('Para')
                    ->searchable()
                    ->options(fn() => Filament::auth()->user()->customer_id ? Company::where('customer_id', Filament::auth()->user()->customer_id)->pluck('name', 'id') : Company::all()->pluck('name', 'id')),
                TextInput::make('name')
                    ->label('Nombre')
                    ->required(),
                TextInput::make('invoice_number')
                    ->label('Número de factura'),
                TextInput::make('description')
                    ->label('Descripción'),
                DatePicker::make('date')
                    ->label('Fecha')
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->default(now()),
                TextInput::make('total_amount')
                    ->label('Monto total')
                    ->numeric(),
                TextInput::make('discount_percentage')
                    ->label('Porcentaje de descuento')
                    ->numeric()
                    ->suffix('%')
                    ->default(0),
                TextInput::make('discount_amount')
                    ->label('Monto de descuento')
                    ->numeric()
                    ->default(0),
                TextInput::make('tax_percentage')
                    ->label('Porcentaje de impuesto')
                    ->numeric()
                    ->suffix('%')
                    ->default(0),
                TextInput::make('tax_amount')
                    ->label('Monto de impuesto')
                    ->numeric()
                    ->default(0),
                TextInput::make('final_amount')
                    ->label('Monto final')
                    ->numeric(),
                TextInput::make('payed_amount')
                    ->label('Monto pagado')
                    ->numeric(),
                Toggle::make('payed')
                    ->label('Pagado')
                    ->required(),
                DatePicker::make('payment_due_date')
                    ->label('Fecha de vencimiento de pago')
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        TextInput::make('name')->label('Nombre')->required(),
                        TextInput::make('quantity')->label('Cantidad')->numeric()->required(),
                        TextInput::make('unit_price')->label('Precio unitario')->numeric()->required(),
                        TextInput::make('discount_percentage')->label('Porcentaje de descuento')->numeric()->default(0),
                        TextInput::make('discount_amount')->label('Monto de descuento')->numeric()->default(0),
                        TextInput::make('total_price')->label('Precio total')->numeric()->required(),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('customer.name')
                    ->label('Customer')
                    ->visible(fn() => Filament::auth()->user()?->isAdmin()),
                TextEntry::make('fromCompany.name')
                    ->label('From'),
                TextEntry::make('toCompany.name')
                    ->label('To'),
                TextEntry::make('name'),
                TextEntry::make('invoice_number')
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('total_amount')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('discount_percentage')
                    ->numeric()
                    ->suffix('%')
                    ->placeholder('-'),
                TextEntry::make('discount_amount')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('tax_percentage')
                    ->numeric()
                    ->suffix('%')
                    ->placeholder('-'),
                TextEntry::make('tax_amount')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('final_amount')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('payed_amount')
                    ->numeric()
                    ->placeholder('-'),
                IconEntry::make('payed')
                    ->boolean(),
                TextEntry::make('payment_due_date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
                RepeatableEntry::make('items')
                    ->schema([
                        TextEntry::make('name'),
                        TextEntry::make('quantity')->numeric(),
                        TextEntry::make('unit_price')->numeric(),
                        TextEntry::make('discount_percentage')->numeric(),
                        TextEntry::make('discount_amount')->numeric(),
                        TextEntry::make('total_price')->numeric(),
                    ])
                    ->columns(4)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Invoice')
            ->columns([
                TextColumn::make('customer.name')
                    ->label('Customer')
                    ->sortable()
                    ->visible(fn() => Filament::auth()->user()?->isAdmin()),
                TextColumn::make('fromCompany.name')
                    ->label('From')
                    ->sortable(),
                TextColumn::make('toCompany.name')
                    ->label('To')
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('invoice_number')
                    ->searchable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total Amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->label('Discount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tax_amount')
                    ->label('Tax')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('final_amount')
                    ->label('Final Amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('payed_amount')
                    ->label('Payed Amount')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('payed')
                    ->label('Payed')
                    ->boolean(),
                TextColumn::make('payment_due_date')
                    ->label('Payment Due Date')
                    ->date()
                    ->sortable(),
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
                TextColumn::make('deleted_at')
                    ->label('Deleted At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->searchable()
                    ->options(Customer::all()->pluck('name', 'id'))
                    ->hidden(fn() => ! Filament::auth()->user()?->is_admin),
                SelectFilter::make('payed')
                    ->label('Pagado')
                    ->options([
                        'true' => 'Pagado',
                        'false' => 'No pagado',
                    ]),
            ])
            ->headerActions([
                Action::make('uploadAndProcess')
                    ->label('Upload & Process')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->schema([
                        FileUpload::make('file')
                            ->label('Archivo a procesar')
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $filePath = $data['file'];
                        // Perform custom processing on the file here
                        // e.g., $contents = Storage::get($filePath);
                        ProcessPDFInvoice::execute($filePath);

                        Notification::make()
                            ->title('Archivo procesado correctamente')
                            ->success()
                            ->send();
                    }),
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
            'index' => ManageInvoices::route('/'),
        ];
    }
}
