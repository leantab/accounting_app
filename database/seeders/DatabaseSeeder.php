<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->admin()->create([
            'name' => 'App',
            'lastname' => 'Admin',
            'email' => 'admin@accountingapp.com',
            'password' => 'password',
        ]);

        DB::table('company_roles')->insert([
            ['name' => 'Vendor'],
            ['name' => 'Customer'],
            ['name' => 'Client'],
            ['name' => 'Provider'],
        ]);

        DB::table('user_roles')->insert([
            ['name' => 'Owner'],
            ['name' => 'Manager'],
            ['name' => 'Employee'],
        ]);
    }
}
