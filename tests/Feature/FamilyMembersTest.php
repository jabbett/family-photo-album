<?php

namespace Tests\Feature;

use App\Models\AllowedEmail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyMembersTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_family_members_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $response = $this->actingAs($admin)->get('/settings/family-members');
        
        $response->assertOk();
        $response->assertSee('Family Members');
        $response->assertSee('email@example.com');
        $response->assertSee('Name (optional)');
        $response->assertSee('Add');
    }

    public function test_non_admin_cannot_access_family_members_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        
        $response = $this->actingAs($user)->get('/settings/family-members');
        
        $response->assertForbidden();
    }

    public function test_family_members_page_shows_empty_state(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $response = $this->actingAs($admin)->get('/settings/family-members');
        
        $response->assertOk();
        $response->assertSee('Family Members');
        // Should show the add form but no existing emails
        $response->assertSee('Add');
    }

    public function test_family_members_page_shows_existing_emails(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        // Create some test emails
        AllowedEmail::create([
            'email' => 'test1@example.com',
            'name' => 'Test User 1',
            'is_active' => true,
        ]);
        
        AllowedEmail::create([
            'email' => 'test2@example.com',
            'name' => null,
            'is_active' => false,
        ]);
        
        $response = $this->actingAs($admin)->get('/settings/family-members');
        
        $response->assertOk();
        $response->assertSee('test1@example.com');
        $response->assertSee('Test User 1');
        $response->assertSee('test2@example.com');
        $response->assertSee('Active');
        $response->assertSee('Inactive');
    }

    public function test_family_members_page_has_add_form(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $response = $this->actingAs($admin)->get('/settings/family-members');
        
        $response->assertOk();
        $response->assertSee('wire:model');
        $response->assertSee('email@example.com');
        $response->assertSee('Name (optional)');
        $response->assertSee('Add');
    }

    public function test_family_members_page_has_action_buttons(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        // Create a test email
        $email = AllowedEmail::create([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'is_active' => true,
        ]);
        
        $response = $this->actingAs($admin)->get('/settings/family-members');
        
        $response->assertOk();
        $response->assertSee('Deactivate');
        $response->assertSee('Delete');
    }

    public function test_family_members_page_uses_correct_layout(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $response = $this->actingAs($admin)->get('/settings/family-members');
        
        $response->assertOk();
        // Check that it uses the settings layout
        $response->assertSee('Profile');
        $response->assertSee('Password');
        $response->assertSee('Appearance');
        $response->assertSee('Site Settings');
        $response->assertSee('Family Members');
    }

    public function test_family_members_page_form_validation_works(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $response = $this->actingAs($admin)->get('/settings/family-members');
        
        $response->assertOk();
        // Check that form validation attributes are present
        $response->assertSee('wire:model');
        $response->assertSee('wire:submit');
        $response->assertSee('Add');
    }
}
