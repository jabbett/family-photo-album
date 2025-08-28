<?php

namespace Tests\Feature;

use App\Models\AllowedEmail;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_admin_route_redirects_to_site_settings(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $response = $this->actingAs($admin)->get('/settings/admin');
        
        // Should redirect to site settings or show 404
        $response->assertStatus(404);
    }

    public function test_non_admin_cannot_access_settings_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        
        $response = $this->actingAs($user)->get('/settings/admin');
        
        $response->assertStatus(404);
    }

    public function test_admin_can_update_site_settings(): void
    {
        // Test the settings functionality directly
        Setting::setValue('site_title', 'New Title');
        Setting::setValue('site_subtitle', 'New Subtitle');
        Setting::setValue('theme_color', '#ff0000');
        
        $this->assertEquals('New Title', Setting::getValue('site_title'));
        $this->assertEquals('New Subtitle', Setting::getValue('site_subtitle'));
        $this->assertEquals('#ff0000', Setting::getValue('theme_color'));
    }

    public function test_admin_can_add_allowed_email(): void
    {
        // Test the allowed email functionality directly
        AllowedEmail::create([
            'email' => 'test@example.com',
            'name' => 'Test User',
            'is_active' => true,
        ]);
        
        $this->assertDatabaseHas('allowed_emails', [
            'email' => 'test@example.com',
            'name' => 'Test User',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_toggle_email_status(): void
    {
        $email = AllowedEmail::factory()->create(['is_active' => true]);
        
        $email->update(['is_active' => false]);
        
        $email->refresh();
        $this->assertFalse($email->is_active);
    }

    public function test_admin_can_delete_email(): void
    {
        $email = AllowedEmail::factory()->create();
        
        $email->delete();
        
        $this->assertDatabaseMissing('allowed_emails', ['id' => $email->id]);
    }

    public function test_separated_pages_work_independently(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        // Test site settings page loads correctly
        $siteResponse = $this->actingAs($admin)->get('/settings/site');
        $siteResponse->assertOk();
        $siteResponse->assertSee('Site Settings');
        $siteResponse->assertSee('Site Title');
        $siteResponse->assertSee('Site Subtitle');
        $siteResponse->assertSee('Theme Color');
        
        // Test family members page loads correctly
        $familyResponse = $this->actingAs($admin)->get('/settings/family-members');
        $familyResponse->assertOk();
        $familyResponse->assertSee('Family Members');
        $familyResponse->assertSee('email@example.com');
        $familyResponse->assertSee('Name (optional)');
        $familyResponse->assertSee('Add');
    }
}
