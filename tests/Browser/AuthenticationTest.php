<?php

use App\Models\AllowedEmail;
use App\Models\User;

it('can log in with valid credentials', function () {
    $user = User::factory()->create([
        'email' => 'user@example.com',
        'password' => bcrypt('password123'),
    ]);

    visit('/login')
        ->assertSee('Log in')
        ->fill('email', 'user@example.com')
        ->fill('password', 'password123')
        ->click('button[type="submit"]')
        ->assertPathIs('/');
});

it('can register a new user with allowed email', function () {
    AllowedEmail::create(['email' => 'newuser@example.com']);

    visit('/register')
        ->fill('name', 'New User')
        ->fill('email', 'newuser@example.com')
        ->fill('password', 'password123')
        ->fill('password_confirmation', 'password123')
        ->click('button[type="submit"]')
        ->assertPathIs('/');

    expect(User::where('email', 'newuser@example.com')->exists())->toBeTrue();
});

it('has no accessibility issues on login page', function () {
    visit('/login')->assertNoAccessibilityIssues();
});

it('has no accessibility issues on register page', function () {
    visit('/register')->assertNoAccessibilityIssues();
});
