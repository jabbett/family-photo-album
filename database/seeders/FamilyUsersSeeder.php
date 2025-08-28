<?php

namespace Database\Seeders;

use App\Models\AllowedEmail;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FamilyUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            if (isset($this->command)) {
                $this->command->warn('FamilyUsersSeeder skipped in production.');
            }

            return;
        }

        // Create admin user (You - the project owner)
        $admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@familyalbum.com',
            'password' => Hash::make('password'),
            'is_admin' => true,
            'email_verified_at' => now(),
        ]);

        // Create family members
        $familyMembers = [
            [
                'name' => 'Spouse',
                'email' => 'spouse@familyalbum.com',
                'password' => Hash::make('password'),
                'is_admin' => false,
            ],
            [
                'name' => 'Child 1',
                'email' => 'child1@familyalbum.com',
                'password' => Hash::make('password'),
                'is_admin' => false,
            ],
            [
                'name' => 'Child 2',
                'email' => 'child2@familyalbum.com',
                'password' => Hash::make('password'),
                'is_admin' => false,
            ],
            [
                'name' => 'Child 3',
                'email' => 'child3@familyalbum.com',
                'password' => Hash::make('password'),
                'is_admin' => false,
            ],
        ];

        foreach ($familyMembers as $member) {
            User::create([
                ...$member,
                'email_verified_at' => now(),
            ]);
        }

        // Add allowed emails for registration
        $allowedEmails = [
            ['email' => 'admin@familyalbum.com', 'name' => 'Admin User'],
            ['email' => 'spouse@familyalbum.com', 'name' => 'Spouse'],
            ['email' => 'child1@familyalbum.com', 'name' => 'Child 1'],
            ['email' => 'child2@familyalbum.com', 'name' => 'Child 2'],
            ['email' => 'child3@familyalbum.com', 'name' => 'Child 3'],
        ];

        foreach ($allowedEmails as $email) {
            AllowedEmail::create($email);
        }

        // Create default settings
        $defaultSettings = [
            'site_title' => 'Family Photo Album',
            'site_subtitle' => 'Sharing our adventures abroad',
            'theme_color' => '#3b82f6', // Blue color
        ];

        foreach ($defaultSettings as $key => $value) {
            Setting::create([
                'key' => $key,
                'value' => $value,
            ]);
        }

        $this->command->info('Family users and settings created successfully!');
        $this->command->info('Admin login: admin@familyalbum.com / password');
        $this->command->info('Family member logins: [email]@familyalbum.com / password');
    }
}
