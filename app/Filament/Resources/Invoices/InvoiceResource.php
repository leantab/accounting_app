<?php

namespace App\Filament\Resources\Invoices;

use App\Filament\Resources\Invoices\Pages\ManageInvoices;
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
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
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
                TextInput::make('customer_id')
                    ->required()
                    ->numeric()
                    ->default(fn() => Filament::auth()->user()?->currentCustomerId())
                    ->visible(fn() => Filament::auth()->user()?->isAdmin()),
                TextInput::make('from_company_id')
                    ->required()
                    ->numeric(),
                TextInput::make('to_company_id')
                    ->required()
                    ->numeric(),
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
                    ->required(),
                DatePicker::make('payment_due_date'),
            ]);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('customer_id')
                    ->numeric(),
                TextEntry::make('from_company_id')
                    ->numeric(),
                TextEntry::make('to_company_id')
                    ->numeric(),
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
                TextEntry::make('deleted_at')
                    ->dateTime()
                    ->visible(fn(Invoice $record): bool => $record->trashed()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('Invoice')
            ->columns([
                TextColumn::make('customer_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('from_company_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('to_company_id')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('date')
                    ->date()
                    ->sortable(),
                TextColumn::make('total_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('payed_amount')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('payed')
                    ->boolean(),
                TextColumn::make('payment_due_date')
                    ->date()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
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
