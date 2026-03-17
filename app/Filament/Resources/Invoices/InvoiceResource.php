<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Invoices\Pages\ManageInvoices;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Invoice;
use BackedEnum;
use Filament\Facades\Filament;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;

class InvoiceResource extends Resource
{
    protected static ?string $model = Invoice::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'Invoice';

    public static function canViewAny(): bool
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return false;
        }

        if (method_exists($user, 'isAdmin') && $user->isAdmin()) {
            return true;
        }

        return $user->currentCustomerId() !== null;
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
                Select::make('from_company_id')
                    ->relationship('from_company')
                    ->label('From')
                    ->searchable()
                    ->options(Company::all()->pluck('name', 'id')),
                Select::make('to_company_id')
                    ->relationship('to_company')
                    ->label('To')
                    ->searchable()
                    ->options(Company::all()->pluck('name', 'id')),
                TextInput::make('name')
                    ->required(),
                Textarea::make('desctiption')
                    ->columnSpanFull(),
                DatePicker::make('date'),
                TextInput::make('total_amount')
                    ->numeric(),
                TextInput::make('payed_amount')
                    ->numeric(),
                Toggle::make('payed')
                    ->label('Payed')
                    ->required(),
                DatePicker::make('payment_due_date')
                    ->label('Payment Due Date'),
                Repeater::make('items')
                    ->relationship('items')
                    ->schema([
                        TextInput::make('name')->required(),
                        TextInput::make('amount')->numeric()->required(),
                        TextInput::make('unit_price')->numeric()->required(),
                        TextInput::make('total_price')->numeric()->required(),
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
                TextEntry::make('desctiption')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('date')
                    ->date()
                    ->placeholder('-'),
                TextEntry::make('total_amount')
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
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->label('Total Amount')
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
                //
            ])
            ->headerActions([
                Action::make('uploadAndProcess')
                    ->label('Upload & Process')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->form([
                        FileUpload::make('file')
                            ->label('File to Process')
                            ->required()
                    ])
                    ->action(function (array $data): void {
                        $filePath = $data['file'];
                        // Perform custom processing on the file here
                        // e.g., $contents = Storage::get($filePath);

                        Notification::make()
                            ->title('File processed successfully')
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
