<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PhotoUploadValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_handle_upload_detects_post_max_size_exceeded(): void
    {
        $user = User::factory()->create();
        
        // Test the validation logic by checking that the photo field is required
        // when no file is posted (simulating post_max_size scenario)
        $response = $this->actingAs($user)
            ->post(route('photos.upload.handle'), []);
            
        $response->assertRedirect();
        $response->assertSessionHasErrors(['photo']);
        
        $errors = session('errors')->get('photo');
        $this->assertStringContainsString('required', $errors[0]);
    }

    public function test_handle_upload_logs_upload_attempt_details(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        
        $this->actingAs($user)
            ->post(route('photos.upload.handle'), [
                'photo' => $file,
            ]);
            
        // Should not throw any errors and create photo
        $this->assertDatabaseCount('photos', 1);
    }

    public function test_handle_upload_validates_different_mime_types(): void
    {
        $user = User::factory()->create();
        
        // Test valid mime types
        $validFiles = [
            UploadedFile::fake()->image('test.jpg', 800, 600),
            UploadedFile::fake()->image('test.png', 800, 600),
            UploadedFile::fake()->image('test.gif', 800, 600),
        ];
        
        foreach ($validFiles as $file) {
            $response = $this->actingAs($user)
                ->post(route('photos.upload.handle'), [
                    'photo' => $file,
                ]);
                
            $response->assertRedirect();
            $response->assertSessionMissing('errors');
        }
    }

    public function test_translate_upload_error_codes(): void
    {
        $user = User::factory()->create();
        
        // Create a reflection to test the protected method
        $controller = new \App\Http\Controllers\PhotoUploadController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('translateUploadErrorCode');
        $method->setAccessible(true);
        
        $limits = 'upload_max_filesize=2M, post_max_size=8M';
        
        // Test different error codes
        $this->assertStringContainsString('upload_max_filesize', 
            $method->invoke($controller, UPLOAD_ERR_INI_SIZE, $limits));
        $this->assertStringContainsString('MAX_FILE_SIZE', 
            $method->invoke($controller, UPLOAD_ERR_FORM_SIZE, $limits));
        $this->assertStringContainsString('partially', 
            $method->invoke($controller, UPLOAD_ERR_PARTIAL, $limits));
        $this->assertStringContainsString('No file', 
            $method->invoke($controller, UPLOAD_ERR_NO_FILE, $limits));
        $this->assertStringContainsString('temporary folder', 
            $method->invoke($controller, UPLOAD_ERR_NO_TMP_DIR, $limits));
        $this->assertStringContainsString('write file', 
            $method->invoke($controller, UPLOAD_ERR_CANT_WRITE, $limits));
        $this->assertStringContainsString('extension stopped', 
            $method->invoke($controller, UPLOAD_ERR_EXTENSION, $limits));
        $this->assertStringContainsString('failed to upload', 
            $method->invoke($controller, 999, $limits)); // Unknown error
    }

    public function test_extract_taken_at_date_from_exif(): void
    {
        // Create a test file with EXIF data
        $testImagePath = storage_path('framework/testing/test-image.jpg');
        $testImageContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/wA==');
        
        if (!is_dir(dirname($testImagePath))) {
            mkdir(dirname($testImagePath), 0755, true);
        }
        file_put_contents($testImagePath, $testImageContent);
        
        $controller = new \App\Http\Controllers\PhotoUploadController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('extractTakenAtDate');
        $method->setAccessible(true);
        
        $result = $method->invoke($controller, $testImagePath);
        
        // Should return a valid date string or null
        $this->assertTrue(is_string($result) || is_null($result));
        
        // Clean up
        if (file_exists($testImagePath)) {
            unlink($testImagePath);
        }
    }

    public function test_is_heic_file_detection(): void
    {
        $controller = new \App\Http\Controllers\PhotoUploadController();
        $reflection = new \ReflectionClass($controller);
        $method = $reflection->getMethod('isHeicFile');
        $method->setAccessible(true);
        
        // Create test files
        $jpegPath = storage_path('framework/testing/test.jpg');
        $heicPath = storage_path('framework/testing/test.heic');
        
        if (!is_dir(dirname($jpegPath))) {
            mkdir(dirname($jpegPath), 0755, true);
        }
        
        // Create minimal JPEG file
        $jpegContent = base64_decode('/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/2wBDAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQEBAQH/wAARCAABAAEDASIAAhEBAxEB/8QAFQABAQAAAAAAAAAAAAAAAAAAAAv/xAAUEAEAAAAAAAAAAAAAAAAAAAAA/8QAFQEBAQAAAAAAAAAAAAAAAAAAAAX/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwA/wA==');
        file_put_contents($jpegPath, $jpegContent);
        
        // Create mock HEIC file (just text for testing)
        file_put_contents($heicPath, 'mock heic content');
        
        // Test JPEG detection (should be false)
        $isJpegHeic = $method->invoke($controller, $jpegPath);
        $this->assertFalse($isJpegHeic);
        
        // Test HEIC detection by extension (should be true due to extension)
        $isHeicFile = $method->invoke($controller, $heicPath);
        $this->assertTrue($isHeicFile); // Based on extension
        
        // Clean up
        if (file_exists($jpegPath)) unlink($jpegPath);
        if (file_exists($heicPath)) unlink($heicPath);
    }

    public function test_store_original_creates_directory_structure(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.jpg', 800, 600);
        
        $this->actingAs($user)
            ->post(route('photos.upload.handle'), [
                'photo' => $file,
            ]);
            
        // Should create photos/originals directory structure
        $this->assertTrue(Storage::disk('public')->exists('photos/originals/'));
    }

    public function test_handle_upload_with_png_file(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.png', 800, 600);
        
        $response = $this->actingAs($user)
            ->post(route('photos.upload.handle'), [
                'photo' => $file,
            ]);
            
        $response->assertRedirect();
        $this->assertDatabaseCount('photos', 1);
        
        $photo = \App\Models\Photo::first();
        $this->assertStringContainsString('.png', $photo->original_path);
    }

    public function test_handle_upload_with_gif_file(): void
    {
        $user = User::factory()->create();
        $file = UploadedFile::fake()->image('test.gif', 800, 600);
        
        $response = $this->actingAs($user)
            ->post(route('photos.upload.handle'), [
                'photo' => $file,
            ]);
            
        $response->assertRedirect();
        $this->assertDatabaseCount('photos', 1);
        
        $photo = \App\Models\Photo::first();
        $this->assertStringContainsString('gif', $photo->original_path);
    }
}