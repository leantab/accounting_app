<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\DB;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::create([
            'name' => 'Leandro',
            'lastname' => 'Tabaj',
            'email' => 'tabajleandro@gmail.com',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
            'is_active' => true,
            'is_admin' => true,
        ]);

        DB::table('company_roles')->insert([
            ['name' => 'Cliente'],
            ['name' => 'Proveedor'],
            ['name' => 'Empleado'],
            ['name' => 'Contratista'],
        ]);

        DB::table('user_roles')->insert([
            ['name' => 'Owner'],
            ['name' => 'Administrador'],
            ['name' => 'Manager'],
            ['name' => 'Empleado'],
        ]);

        DB::table('project_statuses')->insert([
            ['name' => 'Borrador'],
            ['name' => 'En Proceso'],
            ['name' => 'Completado'],
            ['name' => 'Cancelado'],
        ]);

        DB::table('time_tracker_item_types')->insert([
            ['name' => 'Horas'],
            ['name' => 'Jornada'],
            ['name' => 'Media Jornada'],
            ['name' => 'Montaje'],
            ['name' => 'Guardia'],
            ['name' => 'Viáticos'],
            ['name' => 'Gastos'],
        ]);
    }
}
