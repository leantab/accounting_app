<?php

namespace App\Filament\Resources\Invoices;

use App\Actions\ProcessPDFInvoice;
use App\Enums\UserRoleEnum;
use App\Filament\Resources\Invoices\Pages\ManageInvoices;
use App\Models\Company;
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
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
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
                    ->label(__('common.customer'))
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->default(fn () => Filament::auth()->user()?->customer_id)
                    ->visible(fn () => Filament::auth()->user()?->is_admin),
                Select::make('from_company_id')
                    ->relationship('fromCompany')
                    ->label(__('invoices.from'))
                    ->searchable()
                    ->options(fn () => Filament::auth()->user()->customer_id ? Company::where('customer_id', Filament::auth()->user()->customer_id)->pluck('name', 'id') : Company::all()->pluck('name', 'id')),
                Select::make('to_company_id')
                    ->relationship('toCompany')
                    ->label(__('invoices.to'))
                    ->searchable()
                    ->options(fn () => Filament::auth()->user()->customer_id ? Company::where('customer_id', Filament::auth()->user()->customer_id)->pluck('name', 'id') : Company::all()->pluck('name', 'id')),
                TextInput::make('name')
                    ->label(__('common.name'))
                    ->required(),
                TextInput::make('invoice_number')
                    ->label(__('invoices.number')),
                TextInput::make('description')
                    ->label(__('common.description')),
                DatePicker::make('date')
                    ->label(__('common.date'))
                    ->native(false)
                    ->displayFormat('d/m/Y')
                    ->default(now()),
                TextInput::make('total_amount')
                    ->label(__('invoices.total_amount'))
                    ->numeric(),
                TextInput::make('discount_percentage')
                    ->label(__('invoices.discount_percentage'))
                    ->numeric()
                    ->suffix('%')
                    ->default(0),
                TextInput::make('discount_amount')
                    ->label(__('invoices.discount_amount'))
                    ->numeric()
                    ->default(0),
                TextInput::make('tax_percentage')
                    ->label(__('invoices.tax_percentage'))
                    ->numeric()
                    ->suffix('%')
                    ->default(0),
                TextInput::make('tax_amount')
                    ->label(__('invoices.tax_amount'))
                    ->numeric()
                    ->default(0),
                TextInput::make('final_amount')
                    ->label(__('invoices.final_amount'))
                    ->numeric(),
                TextInput::make('payed_amount')
                    ->label(__('invoices.paid_amount'))
                    ->numeric(),
                Toggle::make('payed')
                    ->label(__('common.paid'))
                    ->required(),
                DatePicker::make('payment_due_date')
                    ->label(__('invoices.payment_due_date'))
                    ->native(false)
                    ->displayFormat('d/m/Y'),
                Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        TextInput::make('name')->label(__('common.name'))->required(),
                        TextInput::make('quantity')->label(__('invoices.quantity'))->numeric()->required(),
                        TextInput::make('unit_price')->label(__('invoices.unit_price'))->numeric()->required(),
                        TextInput::make('discount_percentage')->label(__('invoices.discount_percentage'))->numeric()->default(0),
                        TextInput::make('discount_amount')->label(__('invoices.discount_amount'))->numeric()->default(0),
                        TextInput::make('total_price')->label(__('invoices.total_price'))->numeric()->required(),
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
                    ->label(__('common.customer'))
                    ->visible(fn () => Filament::auth()->user()?->is_admin),
                TextEntry::make('fromCompany.name')
                    ->label(__('invoices.from')),
                TextEntry::make('toCompany.name')
                    ->label(__('invoices.to')),
                TextEntry::make('name')
                    ->label(__('common.name')),
                TextEntry::make('invoice_number')
                    ->label(__('invoices.number'))
                    ->placeholder('-'),
                TextEntry::make('description')
                    ->label(__('common.description'))
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('date')
                    ->label(__('common.date'))
                    ->date('d/m/Y')
                    ->placeholder('-'),
                TextEntry::make('total_amount')
                    ->label(__('invoices.total_amount'))
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('discount_percentage')
                    ->label(__('invoices.discount_percentage'))
                    ->numeric()
                    ->suffix('%')
                    ->placeholder('-'),
                TextEntry::make('discount_amount')
                    ->label(__('invoices.discount_amount'))
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('tax_percentage')
                    ->label(__('invoices.tax_percentage'))
                    ->numeric()
                    ->suffix('%')
                    ->placeholder('-'),
                TextEntry::make('tax_amount')
                    ->label(__('invoices.tax_amount'))
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('final_amount')
                    ->label(__('invoices.final_amount'))
                    ->numeric()
                    ->placeholder('-'),
                TextEntry::make('payed_amount')
                    ->label(__('invoices.paid_amount'))
                    ->numeric()
                    ->placeholder('-'),
                IconEntry::make('payed')
                    ->label(__('common.paid'))
                    ->boolean(),
                TextEntry::make('payment_due_date')
                    ->label(__('invoices.due_date'))
                    ->date('d/m/Y')
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->label(__('common.created_at'))
                    ->date('d/m/Y')
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->label(__('common.updated_at'))
                    ->date('d/m/Y')
                    ->placeholder('-'),
                RepeatableEntry::make('items')
                    ->label(__('invoices.line_items'))
                    ->schema([
                        TextEntry::make('name')->label(__('common.name')),
                        TextEntry::make('quantity')->label(__('invoices.quantity'))->numeric(),
                        TextEntry::make('unit_price')->label(__('invoices.unit_price'))->numeric(),
                        TextEntry::make('discount_percentage')->label(__('invoices.discount_percentage'))->numeric(),
                        TextEntry::make('discount_amount')->label(__('invoices.free_amount'))->numeric(),
                        TextEntry::make('total_price')->label(__('invoices.total_price'))->numeric(),
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
                    ->label(__('common.customer'))
                    ->sortable()
                    ->visible(fn () => Filament::auth()->user()?->is_admin),
                TextColumn::make('fromCompany.name')
                    ->label(__('invoices.from'))
                    ->sortable(),
                TextColumn::make('toCompany.name')
                    ->label(__('invoices.to'))
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('common.name'))
                    ->searchable(),
                TextColumn::make('invoice_number')
                    ->label(__('invoices.number'))
                    ->searchable(),
                TextColumn::make('date')
                    ->label(__('common.date'))
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label(__('invoices.total_amount'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('discount_amount')
                    ->label(__('invoices.discount'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('tax_amount')
                    ->label(__('invoices.tax'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('final_amount')
                    ->label(__('invoices.final_amount'))
                    ->numeric()
                    ->sortable(),
                TextColumn::make('payed_amount')
                    ->label(__('invoices.paid_amount'))
                    ->numeric()
                    ->sortable(),
                IconColumn::make('payed')
                    ->label(__('common.paid'))
                    ->boolean(),
                TextColumn::make('payment_due_date')
                    ->label(__('invoices.due_date'))
                    ->date('d/m/Y')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->label(__('common.created_at'))
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label(__('common.updated_at'))
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label(__('common.deleted_at'))
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('customer_id')
                    ->label(__('common.customer'))
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->hidden(fn () => ! Filament::auth()->user()?->is_admin),
                SelectFilter::make('payed')
                    ->label(__('common.paid'))
                    ->options([
                        'true' => __('common.paid'),
                        'false' => __('common.not_paid'),
                    ]),
            ])
            ->headerActions([
                Action::make('uploadAndProcess')
                    ->label(__('invoices.upload_and_process'))
                    ->icon('heroicon-o-arrow-up-tray')
                    ->schema([
                        FileUpload::make('file')
                            ->label(__('invoices.file'))
                            ->required(),
                    ])
                    ->action(function (array $data): void {
                        $filePath = $data['file'];
                        // Perform custom processing on the file here
                        // e.g., $contents = Storage::get($filePath);
                        ProcessPDFInvoice::execute($filePath);

                        Notification::make()
                            ->title(__('invoices.processed'))
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

    public static function getNavigationLabel(): string
    {
        return __('invoices.plural');
    }

    public static function getModelLabel(): string
    {
        return __('common.invoice');
    }
}
