<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $user = Role::firstOrCreate(['name' => 'user']);
        $staffIt = Role::firstOrCreate(['name' => 'staff_it']);

        $user->givePermissionTo([
            'View:Ticket',
            'ViewAny:Ticket',
            'Create:Ticket',
        ]);

        $staffIt->givePermissionTo([
            'View:Ticket',
            'ViewAny:Ticket',
            'Update:Ticket',
        ]);

        User::firstOrCreate(
    ['email' => 'staffit@mail.com'],
    ['name' => 'Staff IT', 'password' => 'password']
)->assignRole($staffIt);

User::firstOrCreate(
    ['email' => 'admin@mail.com'],
    ['name' => 'Admin IT', 'password' => 'password'],
)->assignRole($admin);

User::firstOrCreate(
    ['email' => 'staff@mail.com'],
    ['name' => 'Karyawan', 'password' => 'password'],
)->assignRole($user);

    }
}
