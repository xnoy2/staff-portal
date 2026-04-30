<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@bcf.local'],
            [
                'name'      => 'BCF Admin',
                'password'  => Hash::make('password'),
                'is_active' => true,
                'hire_date' => now()->toDateString(),
            ]
        );
        $admin->assignRole('admin');

        $manager = User::firstOrCreate(
            ['email' => 'manager@bcf.local'],
            [
                'name'      => 'Site Manager',
                'password'  => Hash::make('password'),
                'is_active' => true,
                'hire_date' => now()->toDateString(),
            ]
        );
        $manager->assignRole('manager');

        $siteHead = User::firstOrCreate(
            ['email' => 'sitehead@bcf.local'],
            [
                'name'      => 'Site Head',
                'password'  => Hash::make('password'),
                'is_active' => true,
                'hire_date' => now()->toDateString(),
            ]
        );
        $siteHead->assignRole('site_head');

        $staff = User::firstOrCreate(
            ['email' => 'staff@bcf.local'],
            [
                'name'      => 'John Staff',
                'password'  => Hash::make('password'),
                'is_active' => true,
                'hire_date' => now()->toDateString(),
            ]
        );
        $staff->assignRole('staff');
    }
}
