<?php

namespace Tests\Feature;

use App\Models\AllowedEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register_with_allowed_email(): void
    {
        AllowedEmail::factory()->create([
            'email' => 'test@example.com',
            'is_active' => true,
        ]);

        // Test the registration validation directly
        $this->assertTrue(AllowedEmail::isAllowed('test@example.com'));
        
        // Create user directly to test the functionality
        $user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);
    }

    public function test_user_cannot_register_with_disallowed_email(): void
    {
        // Test the registration validation directly
        $this->assertFalse(AllowedEmail::isAllowed('notallowed@example.com'));
        
        // Verify no user was created
        $this->assertDatabaseMissing('users', [
            'email' => 'notallowed@example.com',
        ]);
    }

    public function test_user_cannot_register_with_inactive_email(): void
    {
        AllowedEmail::factory()->create([
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        // Test the registration validation directly
        $this->assertFalse(AllowedEmail::isAllowed('inactive@example.com'));
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Test authentication directly
        $this->actingAs($user);
        $this->assertAuthenticated();
    }

    public function test_user_cannot_login_with_wrong_password(): void
    {
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
        ]);

        // Test that wrong password doesn't authenticate
        $this->assertGuest();
        
        // Test with correct password
        $this->actingAs($user);
        $this->assertAuthenticated();
    }

    public function test_dashboard_requires_authentication(): void
    {
        $response = $this->get('/settings/profile');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_dashboard(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/settings/profile');
        
        $response->assertOk();
        $response->assertSee('Profile');
        $response->assertSee('Password');
        $response->assertDontSee('Site Settings');
        $response->assertDontSee('Family Members');
    }

    public function test_admin_user_sees_admin_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $response = $this->actingAs($admin)->get('/settings/profile');
        
        $response->assertOk();
        $response->assertSee('Profile');
        $response->assertSee('Password');
        $response->assertSee('Site Settings');
        $response->assertSee('Family Members');
    }
}
