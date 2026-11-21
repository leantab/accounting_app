<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Mail\ResendSender;
use App\Mail\UserInviteMail;
use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Throwable;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label(__('users.create'))
                ->icon('heroicon-o-plus')
                ->color('primary'),
            Action::make('invite')
                ->label(__('users.invite'))
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->schema([
                    Select::make('customer_id')
                        ->label(__('common.customer'))
                        ->options(Customer::all()->pluck('name', 'id'))
                        ->default(Filament::auth()->user()->customer_id)
                        ->hidden(fn () => ! Filament::auth()->user()->is_admin)
                        ->required(),
                    TextInput::make('email')
                        ->label(__('common.email'))
                        ->email()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $mail = $data['email'];
                    $customer = $data['customer_id'];
                    try {
                        ResendSender::send(new UserInviteMail($mail, $customer), $mail);
                    } catch (Throwable $exception) {
                        report($exception);

                        Notification::make()
                            ->title(__('users.invite_failed'))
                            ->body(__('users.try_again_later'))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title(__('users.invite_sent'))
                        ->success()
                        ->send();
                }),
        ];
    }
}
