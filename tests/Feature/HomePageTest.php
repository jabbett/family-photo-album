<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_sign_in_and_no_upload_or_profile(): void
    {
        $response = $this->get(route('home'));
        $response->assertOk();

        // Guests should see Sign In
        $response->assertSee('Sign In');

        // Guests should not see Upload button or initials circle
        $response->assertDontSee('Upload');
        $response->assertDontSee('Log out');
    }

    public function test_authenticated_user_sees_upload_and_profile_initials_and_logout(): void
    {
        $user = User::factory()->create(['name' => 'Test User']);

        $response = $this->actingAs($user)->get(route('home'));
        $response->assertOk();

        // Authenticated users should see the Upload button
        $response->assertSee('Upload');

        // Should show user initials link (e.g., TU)
        $response->assertSee($user->initials());

        // Logout button appears at bottom
        $response->assertSee('Log out');
    }
}


