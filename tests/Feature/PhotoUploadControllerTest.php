<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoUploadControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_show_upload_form_displays_upload_page(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get(route('photos.upload.show'));
            
        $response->assertOk();
        $response->assertViewIs('photos.upload');
    }

    public function test_handle_upload_validates_required_photo(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->post(route('photos.upload.handle'), []);
            
        $response->assertSessionHasErrors(['photo']);
    }

    public function test_handle_upload_validates_file_type(): void
    {
        $user = User::factory()->create();
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);
        
        $response = $this->actingAs($user)
            ->post(route('photos.upload.handle'), [
                'photo' => $invalidFile,
            ]);
            
        $response->assertSessionHasErrors(['photo']);
    }

    public function test_handle_upload_validates_file_size(): void
    {
        $user = User::factory()->create();
        $largeFile = UploadedFile::fake()->image('large.jpg', 2000, 2000)->size(15000); // 15MB
        
        $response = $this->actingAs($user)
            ->post(route('photos.upload.handle'), [
                'photo' => $largeFile,
            ]);
            
        $response->assertSessionHasErrors(['photo']);
    }

    public function test_handle_upload_creates_photo_record(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        
        $response = $this->actingAs($user)
            ->post(route('photos.upload.handle'), [
                'photo' => $file,
            ]);
            
        $response->assertRedirect();
        $this->assertDatabaseHas('photos', [
            'user_id' => $user->id,
            'is_completed' => false,
        ]);
        
        $photo = Photo::first();
        $this->assertNotNull($photo->original_path);
        $this->assertNotNull($photo->width);
        $this->assertNotNull($photo->height);
        Storage::disk('public')->assertExists($photo->original_path);
    }

    public function test_handle_upload_square_image_redirects_to_caption(): void
    {
        $user = User::factory()->create();
        $squareFile = UploadedFile::fake()->image('square.jpg', 800, 800);
        
        $response = $this->actingAs($user)
            ->post(route('photos.upload.handle'), [
                'photo' => $squareFile,
            ]);
            
        $photo = Photo::first();
        $response->assertRedirect(route('photos.caption.show', $photo));
        
        $photo->refresh();
        $this->assertNotNull($photo->thumbnail_path);
        Storage::disk('public')->assertExists($photo->thumbnail_path);
    }

    public function test_handle_upload_non_square_image_redirects_to_crop(): void
    {
        $user = User::factory()->create();
        $rectangularFile = UploadedFile::fake()->image('rect.jpg', 1200, 800);
        
        $response = $this->actingAs($user)
            ->post(route('photos.upload.handle'), [
                'photo' => $rectangularFile,
            ]);
            
        $photo = Photo::first();
        $response->assertRedirect(route('photos.crop.show', $photo));
        
        $photo->refresh();
        $this->assertNull($photo->thumbnail_path);
    }

    public function test_handle_upload_detects_invalid_file(): void
    {
        $user = User::factory()->create();
        
        // Create a mock file that will fail validation (empty file)
        $invalidFile = UploadedFile::fake()->create('invalid.txt', 0);
        
        $response = $this->actingAs($user)
            ->post(route('photos.upload.handle'), [
                'photo' => $invalidFile,
            ]);
            
        $response->assertRedirect();
        $response->assertSessionHasErrors(['photo']);
    }

    public function test_show_crop_form_displays_crop_page(): void
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create(['user_id' => $user->id]);
        
        $response = $this->actingAs($user)
            ->get(route('photos.crop.show', $photo));
            
        $response->assertOk();
        $response->assertViewIs('photos.crop');
        $response->assertViewHas('photo', $photo);
    }

    public function test_show_crop_form_prevents_unauthorized_access(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $photo = Photo::factory()->create(['user_id' => $owner->id]);
        
        $response = $this->actingAs($other)
            ->get(route('photos.crop.show', $photo));
            
        $response->assertForbidden();
    }

    public function test_handle_crop_with_coordinates(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);
        Storage::disk('public')->put('photos/originals/test.jpg', $file->getContent());
        
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'original_path' => 'photos/originals/test.jpg',
            'width' => 1200,
            'height' => 800,
            'caption' => null, // Explicitly no caption for OLD flow
            'is_completed' => false, // Not completed yet
        ]);

        $response = $this->actingAs($user)
            ->post(route('photos.crop.handle', $photo), [
                'crop_x' => 100,
                'crop_y' => 50,
                'crop_size' => 600,
            ]);

        // No caption set, follows OLD flow (crop → caption)
        $response->assertRedirect(route('photos.caption.show', $photo));

        $photo->refresh();
        $this->assertNotNull($photo->thumbnail_path);
        $this->assertFalse($photo->is_completed); // Not completed until caption saved
        Storage::disk('public')->assertExists($photo->thumbnail_path);
    }

    public function test_handle_crop_with_anchor(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);
        Storage::disk('public')->put('photos/originals/test.jpg', $file->getContent());
        
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'original_path' => 'photos/originals/test.jpg',
            'width' => 1200,
            'height' => 800,
            'caption' => null, // Explicitly no caption for OLD flow
            'is_completed' => false, // Not completed yet
        ]);

        $response = $this->actingAs($user)
            ->post(route('photos.crop.handle', $photo), [
                'anchor' => 'center',
            ]);

        // No caption set, follows OLD flow (crop → caption)
        $response->assertRedirect(route('photos.caption.show', $photo));

        $photo->refresh();
        $this->assertNotNull($photo->thumbnail_path);
        $this->assertFalse($photo->is_completed); // Not completed until caption saved
        Storage::disk('public')->assertExists($photo->thumbnail_path);
    }

    public function test_handle_crop_validates_anchor_values(): void
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create(['user_id' => $user->id]);
        
        $response = $this->actingAs($user)
            ->post(route('photos.crop.handle', $photo), [
                'anchor' => 'invalid',
            ]);
            
        $response->assertSessionHasErrors(['anchor']);
    }

    public function test_handle_crop_validates_crop_coordinates(): void
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create(['user_id' => $user->id]);
        
        $response = $this->actingAs($user)
            ->post(route('photos.crop.handle', $photo), [
                'crop_x' => -1,
                'crop_y' => 'invalid',
                'crop_size' => 0,
            ]);
            
        $response->assertSessionHasErrors(['crop_x', 'crop_y', 'crop_size']);
    }

    public function test_handle_crop_prevents_unauthorized_access(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $photo = Photo::factory()->create(['user_id' => $owner->id]);
        
        $response = $this->actingAs($other)
            ->post(route('photos.crop.handle', $photo), [
                'anchor' => 'center',
            ]);
            
        $response->assertForbidden();
    }

    public function test_show_caption_form_displays_caption_page(): void
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create(['user_id' => $user->id]);
        
        $response = $this->actingAs($user)
            ->get(route('photos.caption.show', $photo));
            
        $response->assertOk();
        $response->assertViewIs('photos.caption');
        $response->assertViewHas('photo', $photo);
    }

    public function test_show_caption_form_prevents_unauthorized_access(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $photo = Photo::factory()->create(['user_id' => $owner->id]);
        
        $response = $this->actingAs($other)
            ->get(route('photos.caption.show', $photo));
            
        $response->assertForbidden();
    }

    public function test_handle_caption_saves_caption_and_completes_photo(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('square.jpg', 800, 800);
        Storage::disk('public')->put('photos/originals/square.jpg', $file->getContent());

        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'original_path' => 'photos/originals/square.jpg',
            'width' => 800,
            'height' => 800,
            'is_completed' => false,
        ]);

        $caption = 'Beautiful sunset';

        $response = $this->actingAs($user)
            ->post(route('photos.caption.handle', $photo), [
                'caption' => $caption,
            ]);

        $response->assertRedirect(route('home'));
        $response->assertSessionHas('status', 'Photo uploaded');

        $photo->refresh();
        $this->assertEquals($caption, $photo->caption);
        $this->assertTrue($photo->is_completed);
        $this->assertNotNull($photo->thumbnail_path); // Square photos get auto-cropped
    }

    public function test_handle_caption_accepts_empty_caption(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('square.jpg', 800, 800);
        Storage::disk('public')->put('photos/originals/square.jpg', $file->getContent());

        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'original_path' => 'photos/originals/square.jpg',
            'width' => 800,
            'height' => 800,
            'is_completed' => false,
        ]);

        $response = $this->actingAs($user)
            ->post(route('photos.caption.handle', $photo), [
                'caption' => '',
            ]);

        $response->assertRedirect(route('home'));

        $photo->refresh();
        $this->assertNull($photo->caption);
        $this->assertTrue($photo->is_completed);
    }

    public function test_handle_caption_validates_caption_length(): void
    {
        $user = User::factory()->create();
        $photo = Photo::factory()->create(['user_id' => $user->id]);
        
        $longCaption = str_repeat('a', 501); // Over 500 character limit
        
        $response = $this->actingAs($user)
            ->post(route('photos.caption.handle', $photo), [
                'caption' => $longCaption,
            ]);
            
        $response->assertSessionHasErrors(['caption']);
    }

    public function test_handle_caption_prevents_unauthorized_access(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $photo = Photo::factory()->create(['user_id' => $owner->id]);
        
        $response = $this->actingAs($other)
            ->post(route('photos.caption.handle', $photo), [
                'caption' => 'Test caption',
            ]);
            
        $response->assertForbidden();
    }
}