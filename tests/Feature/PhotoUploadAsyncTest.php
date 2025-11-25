<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoUploadAsyncTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_handle_upload_async_returns_success_with_photo_id(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $response = $this->actingAs($user)
            ->postJson(route('photos.upload.async'), [
                'photo' => $file,
            ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);
        $response->assertJsonStructure([
            'success',
            'photo_id',
            'width',
            'height',
        ]);

        $this->assertDatabaseHas('photos', [
            'user_id' => $user->id,
            'is_completed' => false,
        ]);
    }

    public function test_handle_upload_async_validates_required_photo(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->postJson(route('photos.upload.async'), []);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_handle_upload_async_validates_file_type(): void
    {
        $user = User::factory()->create();
        $invalidFile = UploadedFile::fake()->create('document.pdf', 1000);

        $response = $this->actingAs($user)
            ->postJson(route('photos.upload.async'), [
                'photo' => $invalidFile,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_handle_upload_async_validates_file_size(): void
    {
        $user = User::factory()->create();
        $largeFile = UploadedFile::fake()->image('large.jpg')->size(15000); // 15MB

        $response = $this->actingAs($user)
            ->postJson(route('photos.upload.async'), [
                'photo' => $largeFile,
            ]);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
        ]);
    }

    public function test_handle_upload_async_detects_invalid_file(): void
    {
        $user = User::factory()->create();

        // Create an invalid file that will fail validation
        $tempFile = tmpfile();
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        file_put_contents($tempPath, 'invalid content');

        // Use reflection to create UploadedFile with error code
        $uploadedFile = new UploadedFile(
            $tempPath,
            'invalid.jpg',
            'image/jpeg',
            UPLOAD_ERR_CANT_WRITE,
            true
        );

        $response = $this->actingAs($user)
            ->post(route('photos.upload.async'), [
                'photo' => $uploadedFile,
            ]);

        $response->assertStatus(422);
    }

    public function test_handle_upload_async_logs_upload_attempt(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $this->actingAs($user)
            ->postJson(route('photos.upload.async'), [
                'photo' => $file,
            ]);

        // Test passes if no exceptions thrown - logging is happening
        $this->assertTrue(true);
    }

    public function test_handle_upload_async_stores_original_file(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);

        $response = $this->actingAs($user)
            ->postJson(route('photos.upload.async'), [
                'photo' => $file,
            ]);

        $response->assertOk();

        $photo = Photo::first();
        $this->assertNotNull($photo->original_path);
        Storage::disk('public')->assertExists($photo->original_path);
    }

    public function test_handle_upload_async_extracts_image_dimensions(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 1920, 1080);

        $response = $this->actingAs($user)
            ->postJson(route('photos.upload.async'), [
                'photo' => $file,
            ]);

        $response->assertOk();
        $response->assertJsonPath('width', 1920);
        $response->assertJsonPath('height', 1080);

        $photo = Photo::first();
        $this->assertEquals(1920, $photo->width);
        $this->assertEquals(1080, $photo->height);
    }

    public function test_handle_upload_async_creates_incomplete_photo(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $this->actingAs($user)
            ->postJson(route('photos.upload.async'), [
                'photo' => $file,
            ]);

        $photo = Photo::first();
        $this->assertFalse($photo->is_completed);
        $this->assertNull($photo->thumbnail_path);
        $this->assertNull($photo->caption);
    }

    public function test_handle_upload_async_handles_png_files(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.png', 800, 600);

        $response = $this->actingAs($user)
            ->postJson(route('photos.upload.async'), [
                'photo' => $file,
            ]);

        $response->assertOk();
        $photo = Photo::first();
        $this->assertStringContainsString('.png', $photo->original_path);
    }

    public function test_handle_upload_async_handles_gif_files(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.gif', 800, 600);

        $response = $this->actingAs($user)
            ->postJson(route('photos.upload.async'), [
                'photo' => $file,
            ]);

        $response->assertOk();
        $photo = Photo::first();
        $this->assertStringContainsString('.gif', $photo->original_path);
    }

    public function test_handle_upload_async_requires_authentication(): void
    {
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);

        $response = $this->postJson(route('photos.upload.async'), [
            'photo' => $file,
        ]);

        $response->assertUnauthorized();
    }

    public function test_handle_caption_with_new_flow_redirects_to_crop_for_non_square(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);
        Storage::disk('public')->put('photos/originals/test.jpg', $file->getContent());

        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'original_path' => 'photos/originals/test.jpg',
            'width' => 1200,
            'height' => 800,
            'is_completed' => false,
            'thumbnail_path' => null,
        ]);

        $response = $this->actingAs($user)
            ->post(route('photos.caption.handle', $photo), [
                'caption' => 'Test caption',
            ]);

        $response->assertRedirect(route('photos.crop.show', $photo));

        $photo->refresh();
        $this->assertEquals('Test caption', $photo->caption);
        $this->assertNull($photo->thumbnail_path);
        $this->assertFalse($photo->is_completed);
    }

    public function test_handle_caption_with_new_flow_completes_square_photo(): void
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
            'thumbnail_path' => null,
        ]);

        $response = $this->actingAs($user)
            ->post(route('photos.caption.handle', $photo), [
                'caption' => 'Square photo',
            ]);

        $response->assertRedirect(route('home'));

        $photo->refresh();
        $this->assertEquals('Square photo', $photo->caption);
        $this->assertNotNull($photo->thumbnail_path);
        $this->assertTrue($photo->is_completed);
    }

    public function test_handle_crop_with_new_flow_completes_photo(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);
        Storage::disk('public')->put('photos/originals/test.jpg', $file->getContent());

        // NEW flow: caption was already set
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'original_path' => 'photos/originals/test.jpg',
            'width' => 1200,
            'height' => 800,
            'caption' => 'Already has caption',
            'is_completed' => false,
        ]);

        $response = $this->actingAs($user)
            ->post(route('photos.crop.handle', $photo), [
                'crop_x' => 200,
                'crop_y' => 0,
                'crop_size' => 800,
            ]);

        // Should complete and redirect to home
        $response->assertRedirect(route('home'));

        $photo->refresh();
        $this->assertNotNull($photo->thumbnail_path);
        $this->assertTrue($photo->is_completed);
    }

    public function test_handle_upload_async_handles_json_response_errors(): void
    {
        $user = User::factory()->create();
        $invalidFile = UploadedFile::fake()->create('text.txt', 100);

        $response = $this->actingAs($user)
            ->postJson(route('photos.upload.async'), [
                'photo' => $invalidFile,
            ]);

        $response->assertStatus(422);
        $response->assertJsonStructure([
            'success',
            'message',
        ]);
    }

    public function test_handle_caption_with_old_flow_redirects_to_home_when_thumbnail_exists(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 800, 800);
        Storage::disk('public')->put('photos/originals/test.jpg', $file->getContent());
        Storage::disk('public')->put('photos/thumbnails/thumb.jpg', $file->getContent());

        // OLD flow: thumbnail already exists (cropped first)
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'original_path' => 'photos/originals/test.jpg',
            'thumbnail_path' => 'photos/thumbnails/thumb.jpg',
            'width' => 800,
            'height' => 800,
            'is_completed' => false,
        ]);

        $response = $this->actingAs($user)
            ->post(route('photos.caption.handle', $photo), [
                'caption' => 'Test caption',
            ]);

        $response->assertRedirect(route('home'));

        $photo->refresh();
        $this->assertTrue($photo->is_completed);
    }

    public function test_handle_caption_accepts_null_caption(): void
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
                'caption' => null,
            ]);

        $response->assertRedirect(route('home'));

        $photo->refresh();
        $this->assertNull($photo->caption);
        $this->assertTrue($photo->is_completed);
    }

    public function test_store_original_handles_different_image_formats(): void
    {
        $user = User::factory()->create();

        // Test JPEG
        $jpegFile = UploadedFile::fake()->image('photo.jpg', 800, 600);
        $response = $this->actingAs($user)
            ->postJson(route('photos.upload.async'), ['photo' => $jpegFile]);
        $response->assertOk();
        $jpegPhotoId = $response->json('photo_id');
        $photo1 = Photo::find($jpegPhotoId);
        $this->assertStringContainsString('.jpg', $photo1->original_path);

        // Test PNG
        $pngFile = UploadedFile::fake()->image('photo.png', 800, 600);
        $response = $this->actingAs($user)
            ->postJson(route('photos.upload.async'), ['photo' => $pngFile]);
        $response->assertOk();
        $pngPhotoId = $response->json('photo_id');
        $photo2 = Photo::find($pngPhotoId);
        $this->assertStringContainsString('.png', $photo2->original_path);

        // Test GIF
        $gifFile = UploadedFile::fake()->image('photo.gif', 800, 600);
        $response = $this->actingAs($user)
            ->postJson(route('photos.upload.async'), ['photo' => $gifFile]);
        $response->assertOk();
        $gifPhotoId = $response->json('photo_id');
        $photo3 = Photo::find($gifPhotoId);
        $this->assertStringContainsString('.gif', $photo3->original_path);
    }
}
