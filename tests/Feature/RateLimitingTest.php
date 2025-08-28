<?php

namespace Tests\Feature;

use App\Models\Photo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_is_rate_limited_by_ip(): void
    {
        putenv('FEED_PER_MINUTE=2');

        // Unique IP for this test to avoid bleed-over
        $server = ['REMOTE_ADDR' => '10.0.0.10'];

        // First two requests allowed
        $this->get(route('photos.feed'), $server)->assertOk();
        $this->get(route('photos.feed'), $server)->assertOk();

        // Third request should be throttled
        $this->get(route('photos.feed'), $server)->assertStatus(429);
    }

    public function test_photo_show_is_rate_limited_by_ip(): void
    {
        putenv('SHOW_PER_MINUTE=2');

        Storage::fake('public');
        // Minimal stored files for URL generation (not strictly required for show)
        $original = 'photos/originals/test.jpg';
        Storage::disk('public')->put($original, 'x');
        $thumb = 'photos/thumbnails/test.jpg';
        Storage::disk('public')->put($thumb, 'y');

        $user = \App\Models\User::factory()->create();
        $photo = Photo::create([
            'user_id' => $user->id,
            'original_path' => $original,
            'thumbnail_path' => $thumb,
            'width' => 10,
            'height' => 10,
        ]);

        $server = ['REMOTE_ADDR' => '10.0.0.11'];

        $this->get(route('photos.show', $photo), $server)->assertOk();
        $this->get(route('photos.show', $photo), $server)->assertOk();
        $this->get(route('photos.show', $photo), $server)->assertStatus(429);
    }

    public function test_download_is_rate_limited_by_ip(): void
    {
        putenv('DOWNLOADS_PER_MINUTE=2');

        Storage::fake('public');
        $original = 'photos/originals/test-download.jpg';
        Storage::disk('public')->put($original, str_repeat('a', 100));

        $user = \App\Models\User::factory()->create();
        $photo = Photo::create([
            'user_id' => $user->id,
            'original_path' => $original,
            'width' => 10,
            'height' => 10,
        ]);

        $server = ['REMOTE_ADDR' => '10.0.0.12'];

        $this->get(route('photos.download', $photo), $server)->assertOk();
        $this->get(route('photos.download', $photo), $server)->assertOk();
        $this->get(route('photos.download', $photo), $server)->assertStatus(429);
    }
}


