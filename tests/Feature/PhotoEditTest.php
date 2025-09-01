<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoEditTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_admin_can_edit_any_photo()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'caption' => 'Original caption'
        ]);

        $response = $this->actingAs($admin)
            ->get(route('photos.edit', $photo));

        $response->assertStatus(200);
        $response->assertViewIs('photos.edit');
        $response->assertViewHas('photo', $photo);
    }

    public function test_user_can_edit_own_photos()
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'caption' => 'Original caption'
        ]);

        $response = $this->actingAs($user)
            ->get(route('photos.edit', $photo));

        $response->assertStatus(200);
        $response->assertViewIs('photos.edit');
        $response->assertViewHas('photo', $photo);
    }

    public function test_user_cannot_edit_other_users_photos()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $user1->id,
            'caption' => 'Original caption'
        ]);

        $response = $this->actingAs($user2)
            ->get(route('photos.edit', $photo));

        $response->assertStatus(403);
    }

    public function test_guest_cannot_edit_photos()
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'caption' => 'Original caption'
        ]);

        $response = $this->get(route('photos.edit', $photo));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_can_update_any_photo()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'caption' => 'Original caption'
        ]);

        $response = $this->actingAs($admin)
            ->patch(route('photos.update', $photo), [
                'caption' => 'Updated caption'
            ]);

        $response->assertRedirect(route('photos.show', $photo));
        $response->assertSessionHas('status', 'Caption updated!');

        $photo->refresh();
        $this->assertEquals('Updated caption', $photo->caption);
    }

    public function test_user_can_update_own_photos()
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'caption' => 'Original caption'
        ]);

        $response = $this->actingAs($user)
            ->patch(route('photos.update', $photo), [
                'caption' => 'Updated caption'
            ]);

        $response->assertRedirect(route('photos.show', $photo));
        $response->assertSessionHas('status', 'Caption updated!');

        $photo->refresh();
        $this->assertEquals('Updated caption', $photo->caption);
    }

    public function test_user_cannot_update_other_users_photos()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $user1->id,
            'caption' => 'Original caption'
        ]);

        $response = $this->actingAs($user2)
            ->patch(route('photos.update', $photo), [
                'caption' => 'Updated caption'
            ]);

        $response->assertStatus(403);

        $photo->refresh();
        $this->assertEquals('Original caption', $photo->caption);
    }

    public function test_guest_cannot_update_photos()
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'caption' => 'Original caption'
        ]);

        $response = $this->patch(route('photos.update', $photo), [
            'caption' => 'Updated caption'
        ]);

        $response->assertRedirect(route('login'));

        $photo->refresh();
        $this->assertEquals('Original caption', $photo->caption);
    }

    public function test_caption_can_be_set_to_null()
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'caption' => 'Original caption'
        ]);

        $response = $this->actingAs($user)
            ->patch(route('photos.update', $photo), [
                'caption' => ''
            ]);

        $response->assertRedirect(route('photos.show', $photo));
        $response->assertSessionHas('status', 'Caption updated!');

        $photo->refresh();
        $this->assertNull($photo->caption);
    }

    public function test_caption_validation_enforces_max_length()
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'caption' => 'Original caption'
        ]);

        $longCaption = str_repeat('a', 501); // 501 characters, over the 500 limit

        $response = $this->actingAs($user)
            ->patch(route('photos.update', $photo), [
                'caption' => $longCaption
            ]);

        $response->assertSessionHasErrors('caption');

        $photo->refresh();
        $this->assertEquals('Original caption', $photo->caption);
    }

    public function test_caption_validation_allows_max_length()
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'caption' => 'Original caption'
        ]);

        $maxCaption = str_repeat('a', 500); // Exactly 500 characters

        $response = $this->actingAs($user)
            ->patch(route('photos.update', $photo), [
                'caption' => $maxCaption
            ]);

        $response->assertRedirect(route('photos.show', $photo));
        $response->assertSessionHas('status', 'Caption updated!');

        $photo->refresh();
        $this->assertEquals($maxCaption, $photo->caption);
    }

    public function test_edit_page_shows_current_caption()
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'caption' => 'Current caption'
        ]);

        $response = $this->actingAs($user)
            ->get(route('photos.edit', $photo));

        $response->assertStatus(200);
        $response->assertSee('Current caption');
    }

    public function test_edit_page_handles_null_caption()
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'caption' => null
        ]);

        $response = $this->actingAs($user)
            ->get(route('photos.edit', $photo));

        $response->assertStatus(200);
        // Should show the edit page with empty textarea
        $response->assertSee('Edit Photo');
        $response->assertSee('Save Changes');
    }

    public function test_validation_errors_are_displayed()
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'caption' => 'Original'
        ]);

        $response = $this->actingAs($user)
            ->patch(route('photos.update', $photo), [
                'caption' => str_repeat('a', 501)
            ]);

        $response->assertSessionHasErrors('caption');
        $response->assertStatus(302); // Redirect back to form
    }

    public function test_photo_show_page_has_caption_in_title()
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'caption' => 'Beautiful sunset over the mountains'
        ]);

        $response = $this->get(route('photos.show', $photo));

        $response->assertStatus(200);
        $response->assertSee('<title>Beautiful sunset over the mountains - Photo -', false);
    }

    public function test_photo_show_page_truncates_long_captions_in_title()
    {
        $user = User::factory()->create();
        $longCaption = 'This is a very long caption that should be truncated in the page title because it exceeds the reasonable limit for display in browser tabs and bookmarks';
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'caption' => $longCaption
        ]);

        $response = $this->get(route('photos.show', $photo));

        $response->assertStatus(200);
        $response->assertSee('<title>This is a very long caption that should be truncated in the ... - Photo -', false);
    }

    public function test_photo_show_page_has_default_title_when_no_caption()
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'caption' => null
        ]);

        $response = $this->get(route('photos.show', $photo));

        $response->assertStatus(200);
        $response->assertSee('<title>Photo -', false);
    }
}