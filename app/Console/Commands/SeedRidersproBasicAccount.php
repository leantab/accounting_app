<?php

namespace App\Console\Commands;

use App\Enums\UserRoleEnum;
use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

#[Signature('app:seed-riderspro-basic-account --password=password --tax_id=1234567890')]
#[Description('Create the scaffolding for the riderspro basic account')]
class SeedRidersproBasicAccount extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(string $password, string $tax_id)
    {
        $customer = Customer::create([
            'name' => 'Riders Pro',
            'tax_id' => $tax_id,
        ]);

        $company = Company::create([
            'name' => 'Riders Pro',
            'social_reason' => 'Riders Pro',
            'tax_id' => $tax_id,
            'customer_id' => $customer->id,
            'companyable_id' => $customer->id,
            'companyable_type' => Customer::class,
        ]);

        $user = User::create([
            'name' => 'Fabio',
            'lastname' => 'Razugny',
            'email' => 'admin@riderspro.com',
            'customer_id' => $customer->id,
            'password' => Hash::make($password),
            'role_id' => UserRoleEnum::Owner->value,
        ]);

        $user2 = User::create([
            'name' => 'Sabrina',
            'lastname' => 'Sanchez',
            'email' => 'info@riderspro.com',
            'customer_id' => $customer->id,
            'password' => Hash::make($password),
            'role_id' => UserRoleEnum::Admin->value,
        ]);

        $user3 = User::create([
            'name' => 'Juan',
            'lastname' => 'Gomez',
            'email' => 'juan@riderspro.com',
            'customer_id' => $customer->id,
            'password' => Hash::make($password),
            'role_id' => UserRoleEnum::Manager->value,
        ]);

        $user4 = User::create([
            'name' => 'Maria',
            'lastname' => 'Perez',
            'email' => 'empleado@riderspro.com',
            'customer_id' => $customer->id,
            'password' => Hash::make($password),
            'role_id' => UserRoleEnum::Employee->value,
            'tax_id' => '1234567890',
        ]);

        $company4 = Company::create([
            'name' => $user4->fullName,
            'social_reason' => $user4->fullName,
            'tax_id' => $user4->tax_id,
            'customer_id' => $customer->id,
            'companyable_id' => $user4->id,
            'companyable_type' => User::class,
        ]);
    }
}
