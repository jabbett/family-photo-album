<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Ensure public disk is faked for isolation
        Storage::fake('public');
    }

    public function test_user_can_upload_crop_and_caption_photo(): void
    {
        $user = User::factory()->create();

        // 1) Upload step
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('test.jpg', 1600, 1200)->size(3500); // ~3.5 MB

        $response = $this->post(route('photos.upload.handle'), [
            'photo' => $file,
        ]);

        $response->assertRedirect();

        $photo = Photo::first();
        $this->assertNotNull($photo, 'Photo record should be created after upload');
        $this->assertNotEmpty($photo->original_path);
        Storage::disk('public')->assertExists($photo->original_path);
        $this->assertNull($photo->thumbnail_path, 'Thumbnail not created yet before cropping');
        $this->assertFalse($photo->is_completed, 'Photo should not be completed after upload step');

        // 2) Crop step (explicit coordinates)
        $cropResponse = $this->post(route('photos.crop.handle', $photo), [
            'crop_x' => 100,
            'crop_y' => 50,
            'crop_size' => 500,
        ]);

        $cropResponse->assertRedirect(route('photos.caption.show', $photo));
        $photo->refresh();
        $this->assertNotNull($photo->thumbnail_path);
        Storage::disk('public')->assertExists($photo->thumbnail_path);
        $this->assertFalse($photo->is_completed, 'Photo should not be completed after crop step');

        // 3) Caption step
        $caption = 'A day at the beach';
        $captionResponse = $this->post(route('photos.caption.handle', $photo), [
            'caption' => $caption,
        ]);

        $captionResponse->assertRedirect(route('home'));
        $photo->refresh();
        $this->assertEquals($caption, $photo->caption);
        $this->assertTrue($photo->is_completed, 'Photo should be completed after caption step');

        // Home page should render successfully and include the completed photo
        $this->get(route('home'))->assertOk()->assertSee($photo->caption);
    }

    public function test_abandoned_photos_do_not_appear_in_album(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $file = UploadedFile::fake()->image('test.jpg', 1600, 1200)->size(3500);

        // 1) Upload step
        $this->post(route('photos.upload.handle'), ['photo' => $file]);
        $photo = Photo::first();
        $this->assertFalse($photo->is_completed);

        // 2) Crop step
        $this->post(route('photos.crop.handle', $photo), [
            'crop_x' => 100,
            'crop_y' => 50,
            'crop_size' => 500,
        ]);
        $photo->refresh();
        $this->assertFalse($photo->is_completed);

        // 3) Abandon at caption step (don't submit caption)
        // Photo should exist in database but not be completed
        $this->assertTrue(Photo::count() === 1);
        $this->assertFalse($photo->is_completed);

        // Home page should not show the abandoned photo
        $homeResponse = $this->get(route('home'));
        $homeResponse->assertOk();
        
        // The photo grid should be empty or not contain this photo's ID
        $homeResponse->assertDontSee("data-photo-id=\"{$photo->id}\"", false);

        // Feed endpoint should also exclude the photo
        $feedResponse = $this->get(route('photos.feed'));
        $feedResponse->assertOk();
        $feedData = $feedResponse->json();
        $this->assertEmpty($feedData['data'], 'Feed should not contain incomplete photos');
    }
}


