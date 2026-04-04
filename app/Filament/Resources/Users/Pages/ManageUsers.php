<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Mail\UserInviteMail;
use App\Models\Customer;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\ManageRecords;
use Illuminate\Support\Facades\Mail;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Crear usuario')
                ->icon('heroicon-o-plus')
                ->color('primary'),
            Action::make('invite')
                ->label('Invitar usuario')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->schema([
                    Select::make('customer_id')
                        ->label('Customer')
                        ->options(Customer::all()->pluck('name', 'id'))
                        ->default(Filament::auth()->user()->customer_id)
                        ->hidden(fn() => ! Filament::auth()->user()->is_admin)
                        ->required(),
                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required(),
                ])
                ->action(function (array $data) {
                    $mail = $data['email'];
                    $customer = $data['customer_id'];
                    // Enviar un main de invitacion
                    Mail::to($mail)->send(new UserInviteMail($mail, $customer));
                }),
        ];
    }
}
