<?php

namespace Tests\Feature;

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageSanitizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_preserves_exif_when_source_has_exif(): void
    {
        // This test requires real EXIF data to validate EXIF preservation end-to-end.
        // Place a JPEG with real EXIF at tests/Fixtures/exif-sample.jpg
        $fixture = base_path('tests/Fixtures/exif-sample.jpg');

        if (!function_exists('exif_read_data')) {
            $this->fail('PHP EXIF extension not enabled; environment cannot safely process images.');
        }

        if (!file_exists($fixture)) {
            $this->markTestSkipped('EXIF fixture not present at tests/Fixtures/exif-sample.jpg');
        }

        $preExif = @exif_read_data($fixture, 'EXIF');
        $this->assertFalse($preExif === false || empty($preExif), 'Fixture must contain readable EXIF metadata');

        Storage::fake('public');

        $user = User::factory()->create();
        $this->actingAs($user);

        $upload = new UploadedFile($fixture, 'exif-sample.jpg', 'image/jpeg', null, true);

        $response = $this->post(route('photos.upload.handle'), [
            'photo' => $upload,
        ]);

        $response->assertRedirect();

        $photo = Photo::first();
        $this->assertNotNull($photo);

        $this->assertTrue(Storage::disk('public')->exists($photo->original_path));
        $sanitizedPath = Storage::disk('public')->path($photo->original_path);

        $postExif = @exif_read_data($sanitizedPath, 'EXIF');
        $this->assertTrue($postExif !== false && !empty($postExif), 'EXIF data should be preserved');

        // Ensure thumbnail exists (square uploads create it immediately; otherwise, invoke crop to generate)
        if (is_null($photo->thumbnail_path)) {
            $this->post(route('photos.crop.handle', $photo), ['anchor' => 'center'])->assertRedirect();
            $photo->refresh();
        }

        $this->assertNotNull($photo->thumbnail_path);
        $this->assertTrue(Storage::disk('public')->exists($photo->thumbnail_path));
        $thumbPath = Storage::disk('public')->path($photo->thumbnail_path);
        $thumbExif = @exif_read_data($thumbPath, 'EXIF');
        $this->assertTrue($thumbExif === false || empty($thumbExif), 'Thumbnail should not have EXIF (created via Imagick)');
    }
}


