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

#[Signature('app:seed-riderspro-basic-account')]
#[Description('Create the scaffolding for the riderspro basic account')]
class SeedRidersproBasicAccount extends Command
{
    /**
     * Execute the console command.
     */
    public function handle()
    {
        $password = 'Frutilla85!';
        $tax_id = '30-71610378-0';

        $customer = Customer::create([
            'name' => 'Riders Pro',
            'email' => 'info@riderspro.com.ar',
            'tax_id' => $tax_id,
        ]);

        $this->info('Customer created successfully. ID: ' . $customer->id);

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
            'email' => 'fabio@riderspro.com.ar',
            'customer_id' => $customer->id,
            'password' => Hash::make($password),
            'user_role_id' => UserRoleEnum::Owner->value,
            'email_verified_at' => now(),
        ]);

        $user2 = User::create([
            'name' => 'Sabrina',
            'lastname' => 'Sanchez',
            'email' => 'info@riderspro.com.ar',
            'customer_id' => $customer->id,
            'password' => Hash::make($password),
            'user_role_id' => UserRoleEnum::Admin->value,
            'email_verified_at' => now(),
        ]);

        $user3 = User::create([
            'name' => 'Maro',
            'lastname' => 'Papardopulos',
            'email' => 'maro@riderspro.com.ar',
            'customer_id' => $customer->id,
            'password' => Hash::make($password),
            'user_role_id' => UserRoleEnum::Manager->value,
            'email_verified_at' => now(),
        ]);

        $user4 = User::create([
            'name' => 'Maria',
            'lastname' => 'Perez',
            'email' => 'empleado@riderspro.com.ar',
            'customer_id' => $customer->id,
            'password' => Hash::make($password),
            'user_role_id' => UserRoleEnum::Employee->value,
            'tax_id' => '1234567890',
            'email_verified_at' => now(),
        ]);

        $company4 = Company::create([
            'name' => $user4->fullName,
            'social_reason' => $user4->fullName,
            'tax_id' => $user4->tax_id,
            'customer_id' => $customer->id,
            'companyable_id' => $user4->id,
            'companyable_type' => User::class,
        ]);

        $this->info('Company created successfully. ID: ' . $company->id);
        $this->info('User created successfully. ID: ' . $user->id);
        $this->info('User created successfully. ID: ' . $user2->id);
        $this->info('User created successfully. ID: ' . $user3->id);
        $this->info('User created successfully. ID: ' . $user4->id);
        $this->info('Company created successfully. ID: ' . $company4->id);
        $this->info('Riderspro basic account created successfully');
    }
}
