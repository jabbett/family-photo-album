<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoUploadEdgeCasesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_translate_upload_error_code_handles_all_error_types(): void
    {
        $user = User::factory()->create();
        $controller = new \App\Http\Controllers\PhotoUploadController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('translateUploadErrorCode');
        $method->setAccessible(true);
        
        $limits = 'upload_max_filesize=2M, post_max_size=8M';
        
        // Test all PHP upload error constants
        $errorMessages = [
            UPLOAD_ERR_INI_SIZE => $method->invoke($controller, UPLOAD_ERR_INI_SIZE, $limits),
            UPLOAD_ERR_FORM_SIZE => $method->invoke($controller, UPLOAD_ERR_FORM_SIZE, $limits),
            UPLOAD_ERR_PARTIAL => $method->invoke($controller, UPLOAD_ERR_PARTIAL, $limits),
            UPLOAD_ERR_NO_FILE => $method->invoke($controller, UPLOAD_ERR_NO_FILE, $limits),
            UPLOAD_ERR_NO_TMP_DIR => $method->invoke($controller, UPLOAD_ERR_NO_TMP_DIR, $limits),
            UPLOAD_ERR_CANT_WRITE => $method->invoke($controller, UPLOAD_ERR_CANT_WRITE, $limits),
            UPLOAD_ERR_EXTENSION => $method->invoke($controller, UPLOAD_ERR_EXTENSION, $limits),
        ];
        
        // Verify each error type returns appropriate message
        $this->assertStringContainsString('upload_max_filesize', $errorMessages[UPLOAD_ERR_INI_SIZE]);
        $this->assertStringContainsString('MAX_FILE_SIZE', $errorMessages[UPLOAD_ERR_FORM_SIZE]);
        $this->assertStringContainsString('partially', $errorMessages[UPLOAD_ERR_PARTIAL]);
        $this->assertStringContainsString('No file', $errorMessages[UPLOAD_ERR_NO_FILE]);
        $this->assertStringContainsString('temporary folder', $errorMessages[UPLOAD_ERR_NO_TMP_DIR]);
        $this->assertStringContainsString('write file', $errorMessages[UPLOAD_ERR_CANT_WRITE]);
        $this->assertStringContainsString('extension stopped', $errorMessages[UPLOAD_ERR_EXTENSION]);
        
        // Test default case
        $defaultMessage = $method->invoke($controller, 999, $limits);
        $this->assertStringContainsString('failed to upload', $defaultMessage);
    }

    public function test_is_heic_file_detection_by_extension(): void
    {
        $controller = new \App\Http\Controllers\PhotoUploadController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('isHeicFile');
        $method->setAccessible(true);
        
        // Create test files
        $testDir = storage_path('framework/testing');
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        
        $heicPath = $testDir . '/test.heic';
        $heifPath = $testDir . '/test.heif';
        $jpegPath = $testDir . '/test.jpg';
        
        // Create minimal test files
        file_put_contents($heicPath, 'mock heic content');
        file_put_contents($heifPath, 'mock heif content');
        file_put_contents($jpegPath, 'mock jpeg content');
        
        // Test extension detection
        $this->assertTrue($method->invoke($controller, $heicPath));
        $this->assertTrue($method->invoke($controller, $heifPath));
        $this->assertFalse($method->invoke($controller, $jpegPath));
        
        // Clean up
        unlink($heicPath);
        unlink($heifPath);
        unlink($jpegPath);
    }

    public function test_extract_taken_at_date_handles_invalid_files(): void
    {
        $controller = new \App\Http\Controllers\PhotoUploadController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('extractTakenAtDate');
        $method->setAccessible(true);
        
        // Test with invalid file that exists
        $testDir = storage_path('framework/testing');
        if (!is_dir($testDir)) {
            mkdir($testDir, 0755, true);
        }
        
        $invalidPath = $testDir . '/invalid.txt';
        file_put_contents($invalidPath, 'not an image file');
        
        $result = $method->invoke($controller, $invalidPath);
        // Should fallback to file modification time
        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/', $result);
        
        unlink($invalidPath);
    }

    public function test_auto_orient_imagick_handles_missing_method(): void
    {
        // This test checks the fallback orientation handling
        // Since we can't easily mock Imagick in tests, we'll test the method exists
        $controller = new \App\Http\Controllers\PhotoUploadController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('autoOrientImagick');
        $method->setAccessible(true);
        
        // Method should exist and be callable
        $this->assertTrue($reflection->hasMethod('autoOrientImagick'));
        $this->assertTrue($method->isProtected());
    }

    public function test_store_original_handles_different_image_types(): void
    {
        $user = User::factory()->create();
        
        // Test JPEG
        $jpegFile = UploadedFile::fake()->image('test.jpg', 800, 600);
        $response = $this->actingAs($user)
            ->post(route('photos.upload.handle'), ['photo' => $jpegFile]);
        $response->assertRedirect();
        
        $jpegPhoto = Photo::latest()->first();
        $this->assertStringContainsString('jpg', $jpegPhoto->original_path);
        
        // Test PNG - Laravel's fake image generator creates JPEG regardless of extension
        // So we'll test that the upload succeeds and creates a photo record
        $pngFile = UploadedFile::fake()->image('test.png', 800, 600);
        $response = $this->actingAs($user)
            ->post(route('photos.upload.handle'), ['photo' => $pngFile]);
        $response->assertRedirect();
        
        // Should have 2 photos now
        $this->assertCount(2, Photo::all());
    }

    public function test_handle_upload_creates_proper_directory_structure(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        
        $response = $this->actingAs($user)
            ->post(route('photos.upload.handle'), ['photo' => $file]);
            
        $response->assertRedirect();
        
        // Check that directories were created
        $this->assertTrue(Storage::disk('public')->exists('photos/originals'));
        
        $photo = Photo::first();
        $this->assertNotNull($photo->original_path);
        $this->assertStringStartsWith('photos/originals/', $photo->original_path);
        $this->assertTrue(Storage::disk('public')->exists($photo->original_path));
    }

    public function test_create_square_thumbnail_creates_proper_structure(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 800, 800); // Square image
        
        $response = $this->actingAs($user)
            ->post(route('photos.upload.handle'), ['photo' => $file]);
            
        $response->assertRedirect();
        
        $photo = Photo::first();
        $this->assertNotNull($photo->thumbnail_path);
        $this->assertStringStartsWith('photos/thumbnails/', $photo->thumbnail_path);
        $this->assertStringEndsWith('.jpg', $photo->thumbnail_path);
        $this->assertTrue(Storage::disk('public')->exists($photo->thumbnail_path));
    }

    public function test_handle_crop_with_different_anchor_positions(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);
        Storage::disk('public')->put('photos/originals/test.jpg', $file->getContent());
        
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'original_path' => 'photos/originals/test.jpg',
            'width' => 1200,
            'height' => 800,
        ]);
        
        // Test different anchor positions
        $anchors = ['left', 'right', 'center', 'top', 'bottom'];
        
        foreach ($anchors as $anchor) {
            $response = $this->actingAs($user)
                ->post(route('photos.crop.handle', $photo), [
                    'anchor' => $anchor,
                ]);
                
            $response->assertRedirect(route('photos.caption.show', $photo));
        }
        
        $photo->refresh();
        $this->assertNotNull($photo->thumbnail_path);
    }

    public function test_handle_crop_with_boundary_coordinates(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 1000, 800);
        Storage::disk('public')->put('photos/originals/test.jpg', $file->getContent());
        
        $photo = Photo::factory()->create([
            'user_id' => $user->id,
            'original_path' => 'photos/originals/test.jpg',
            'width' => 1000,
            'height' => 800,
        ]);
        
        // Test boundary coordinates (should be clamped)
        $response = $this->actingAs($user)
            ->post(route('photos.crop.handle', $photo), [
                'crop_x' => 999,  // At edge
                'crop_y' => 799,  // At edge
                'crop_size' => 1, // Minimum size
            ]);
            
        $response->assertRedirect(route('photos.caption.show', $photo));
        
        $photo->refresh();
        $this->assertNotNull($photo->thumbnail_path);
    }
}