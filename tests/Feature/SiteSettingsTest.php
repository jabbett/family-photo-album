<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_access_site_settings_page(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $response = $this->actingAs($admin)->get('/settings/site');
        
        $response->assertOk();
        $response->assertSee('Site Settings');
        $response->assertSee('Site Title');
        $response->assertSee('Site Subtitle');
        $response->assertSee('Theme Color');
    }

    public function test_non_admin_cannot_access_site_settings_page(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        
        $response = $this->actingAs($user)->get('/settings/site');
        
        $response->assertForbidden();
    }

    public function test_site_settings_page_loads_with_default_values(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $response = $this->actingAs($admin)->get('/settings/site');
        
        $response->assertOk();
        $response->assertSee('Family Photo Album'); // Default title
        $response->assertSee('Sharing our adventures abroad'); // Default subtitle
        $response->assertSee('#3b82f6'); // Default theme color
    }

    public function test_site_settings_page_loads_with_custom_values(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        // Set custom values
        Setting::setValue('site_title', 'Custom Title');
        Setting::setValue('site_subtitle', 'Custom Subtitle');
        Setting::setValue('theme_color', '#ff0000');
        
        $response = $this->actingAs($admin)->get('/settings/site');
        
        $response->assertOk();
        $response->assertSee('Custom Title');
        $response->assertSee('Custom Subtitle');
        $response->assertSee('#ff0000');
    }

    public function test_site_settings_form_validation_works(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $response = $this->actingAs($admin)->get('/settings/site');
        
        $response->assertOk();
        // Check that form validation attributes are present
        $response->assertSee('required');
        $response->assertSee('wire:model');
        $response->assertSee('Site Title');
        $response->assertSee('Site Subtitle');
        $response->assertSee('Theme Color');
    }

    public function test_site_settings_page_has_save_button(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $response = $this->actingAs($admin)->get('/settings/site');
        
        $response->assertOk();
        $response->assertSee('Save Settings');
    }

    public function test_site_settings_page_uses_correct_layout(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        
        $response = $this->actingAs($admin)->get('/settings/site');
        
        $response->assertOk();
        // Check that it uses the settings layout
        $response->assertSee('Profile');
        $response->assertSee('Password');
        $response->assertSee('Appearance');
        $response->assertSee('Site Settings');
        $response->assertSee('Family Members');
    }
}
