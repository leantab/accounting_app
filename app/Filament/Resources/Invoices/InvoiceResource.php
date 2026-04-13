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

    protected static ?string $modelLabel = 'Factura';

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

        return in_array($user->user_role_id, [UserRoleEnum::Admin->value, UserRoleEnum::Owner->value]);
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
                    ->label('Cliente')
                    ->relationship('customer', 'name')
                    ->searchable()
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
                    ->label('Cliente')
                    ->visible(fn() => Filament::auth()->user()?->is_admin),
                TextEntry::make('fromCompany.name')
                    ->label('De'),
                TextEntry::make('toCompany.name')
                    ->label('Para'),
                TextEntry::make('name')
                    ->label('Nombre'),
                TextEntry::make('invoice_number')
                    ->label('Número de factura')
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->label('Descripción')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->placeholder('-'),
                TextEntry::make('total_amount')
                    ->label('Monto total')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('discount_percentage')
                    ->label('Porcentaje de descuento')
                    ->numeric()
                    ->suffix('%')
                    ->placeholder('-'),
                TextEntry::make('discount_amount')
                    ->label('Monto de descuento')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('tax_percentage')
                    ->label('Porcentaje de impuesto')
                    ->numeric()
                    ->suffix('%')
                    ->placeholder('-'),
                TextEntry::make('tax_amount')
                    ->label('Monto de impuesto')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('final_amount')
                    ->label('Monto final')
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('payed_amount')
                    ->label('Monto pagado')
                    ->numeric()
                    ->placeholder('-'),
                IconEntry::make('payed')
                    ->label('Pagado')
                    ->boolean(),
                TextEntry::make('payment_due_date')
                    ->label('Fecha de vencimiento')
                    ->date('d/m/Y')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label('Fecha de creación')
                    ->date('d/m/Y')
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label('Fecha de actualización')
                    ->date('d/m/Y')
                    ->placeholder('-'),
                RepeatableEntry::make('items')
                    ->label('Conceptos')
                    ->schema([
                        TextEntry::make('name')->label('Nombre'),
                        TextEntry::make('quantity')->label('Cantidad')->numeric(),
                        TextEntry::make('unit_price')->label('Precio unitario')->numeric(),
                        TextEntry::make('discount_percentage')->label('Porcentaje de descuento')->numeric(),
                        TextEntry::make('discount_amount')->label('Monto libre')->numeric(),
                        TextEntry::make('total_price')->label('Precio total')->numeric(),
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
                    ->label('Cliente')
                    ->sortable()
                    ->visible(fn() => Filament::auth()->user()?->is_admin),
                TextColumn::make('fromCompany.name')
                    ->label('De')
                    ->sortable(),
                TextColumn::make('toCompany.name')
                    ->label('Para')
                    ->sortable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable(),
                TextColumn::make('invoice_number')
                    ->label('Número de factura')
                    ->searchable(),
                TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Monto total')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->label('Descuento')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tax_amount')
                    ->label('Impuesto')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('final_amount')
                    ->label('Monto final')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('payed_amount')
                    ->label('Monto pagado')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('payed')
                    ->label('Pagado')
                    ->boolean(),
                TextColumn::make('payment_due_date')
                    ->label('Fecha de vencimiento')
                    ->date('d/m/Y')
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
                TextColumn::make('deleted_at')
                    ->label('Fecha de eliminación')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label('Cliente')
                    ->relationship('customer', 'name')
                    ->searchable()
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
