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

        $user->givePermissionTo([
            'View:Ticket',
            'ViewAny:Ticket',
            'Create:Ticket',
        ]);

        User::firstOrCreate(
            ['email' => 'admin@mail.com'],
            ['name' => 'admin IT', 'password' => 'password'],
        )->assignRole($admin);

        User::firstOrCreate(
            ['email' => 'staff@mail.com'],
            ['name' => 'staff', 'password' => 'password'],
        )->assignRole($user);
    }
}
