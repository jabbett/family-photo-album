<?php

use App\Models\User;

it('requires authentication to access upload page', function () {
    visit('/photos/upload')->assertPathIs('/login');
});

it('can access upload page when authenticated', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    visit('/login')
        ->fill('email', 'test@example.com')
        ->fill('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/')
        ->navigate('/photos/upload')
        ->assertPathIs('/photos/upload')
        ->assertSee('Upload');
});

it('shows file picker on upload page', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    visit('/login')
        ->fill('email', 'test@example.com')
        ->fill('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/')
        ->navigate('/photos/upload')
        ->assertVisible('input[type="file"]')
        ->assertVisible('button:has-text("Continue")');
});

it('has no accessibility issues on upload page', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    visit('/login')
        ->fill('email', 'test@example.com')
        ->fill('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/')
        ->navigate('/photos/upload')
        ->assertNoAccessibilityIssues();
});

it('has no JavaScript errors on upload page', function () {
    $user = User::factory()->create(['email' => 'test@example.com']);

    visit('/login')
        ->fill('email', 'test@example.com')
        ->fill('password', 'password')
        ->click('button[type="submit"]')
        ->assertPathIs('/')
        ->navigate('/photos/upload')
        ->assertNoJavaScriptErrors();
});
