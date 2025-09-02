<?php

use App\Models\Photo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function uploadFakePhoto(User $user, ?string $caption = null, ?string $takenAt = null): Photo {
    Storage::fake('public');
    $photo = Photo::factory()->create([
        'user_id' => $user->id,
        'original_path' => 'photos/originals/test.jpg',
        'thumbnail_path' => 'photos/thumbnails/test-thumb.jpg',
        'width' => 1000,
        'height' => 800,
        'caption' => $caption,
        'taken_at' => $takenAt,
    ]);
    Storage::disk('public')->put($photo->original_path, 'fake');
    Storage::disk('public')->put($photo->thumbnail_path, 'fake');
    return $photo;
}

it('shows header with link, title and navigation', function () {
    $user = User::factory()->create();
    $older = uploadFakePhoto($user, takenAt: now()->subDays(2)->toDateTimeString());
    $current = uploadFakePhoto($user, takenAt: now()->subDay()->toDateTimeString());
    $newer = uploadFakePhoto($user, takenAt: now()->toDateTimeString());

    $response = $this->get(route('photos.show', $current));
    $response->assertOk();
    $response->assertSee(route('home'), false);
    $response->assertSee('Family Photo Album', false);
    // Has prev and next links
    $response->assertSee(route('photos.show', $older), false);
    $response->assertSee(route('photos.show', $newer), false);
});

it('disables navigation at the ends', function () {
    $user = User::factory()->create();
    $only = uploadFakePhoto($user, takenAt: now()->toDateTimeString());

    $response = $this->get(route('photos.show', $only));
    $response->assertOk();
    // Disabled state renders anchors with # links
    $response->assertSee('aria-disabled="true"', false);
});

it('shows uploader and relative taken time', function () {
    $user = User::factory()->create(['name' => 'Test Uploader']);
    $photo = uploadFakePhoto($user, takenAt: now()->subHour()->toDateTimeString());

    $response = $this->get(route('photos.show', $photo));
    $response->assertOk();
    $response->assertSee('Test Uploader');
    $response->assertSee('ago');
});

it('shows caption overlay and footer absolute date', function () {
    $user = User::factory()->create();
    $takenAt = now()->setTime(0, 0, 0);
    $photo = uploadFakePhoto($user, caption: 'Hello world', takenAt: $takenAt->toDateTimeString());

    $response = $this->get(route('photos.show', $photo));
    $response->assertOk();
    $response->assertSee('Hello world');
    $response->assertSee($takenAt->format('j M Y'));
});

it('allows download of the original image', function () {
    $user = User::factory()->create();
    $photo = uploadFakePhoto($user, takenAt: now()->toDateTimeString());

    $response = $this->get(route('photos.download', $photo));
    $response->assertOk();
    $response->assertHeader('content-disposition');
});

it('shows delete button to admin and uploader only and deletes photo', function () {
    $uploader = User::factory()->create();
    $other = User::factory()->create();
    $admin = User::factory()->create(['is_admin' => true]);
    $photo = uploadFakePhoto($uploader, takenAt: now()->toDateTimeString());

    // Uploader sees delete and can delete
    $this->actingAs($uploader);
    $this->delete(route('photos.destroy', $photo))->assertRedirect();
    $this->assertDatabaseMissing('photos', ['id' => $photo->id]);

    // Recreate for other checks
    $photo = uploadFakePhoto($uploader, takenAt: now()->toDateTimeString());

    // Other user cannot delete
    $this->actingAs($other);
    $this->delete(route('photos.destroy', $photo))->assertForbidden();

    // Admin can delete
    $this->actingAs($admin);
    $this->delete(route('photos.destroy', $photo))->assertRedirect();
});


