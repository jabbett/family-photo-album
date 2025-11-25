<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Controllers\PhotoUploadController;
use App\Http\Controllers\PhotoController;
use App\Models\Post;

Route::get('/debug-php', function() {
    return response()->json([
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'max_file_uploads' => ini_get('max_file_uploads'),
        'memory_limit' => ini_get('memory_limit'),
        'loaded_ini' => php_ini_loaded_file()
    ]);
});

Route::get('/', function () {
    $perPage = 20;
    $paginator = Post::with(['photos' => function($query) {
            $query->where('position', 0); // Load cover photo only
        }])
        ->withCount('photos') // Add photo count for collection indicator
        ->where('is_completed', true)
        ->orderByRaw('COALESCE(display_date, created_at) DESC')
        ->paginate($perPage);

    return view('photo-album', [
        'posts' => $paginator->items(),
        'nextPage' => $paginator->hasMorePages() ? ($paginator->currentPage() + 1) : null,
        'perPage' => $perPage,
    ]);
})->name('home');

// Public photo routes (guest throttled)
// Note: URLs use /photo/{post} for backward compatibility
Route::get('photo/{post}', [PhotoController::class, 'show'])
    ->middleware('throttle:photo-show')
    ->name('photos.show');
Route::get('photo/{photo}/download', [PhotoController::class, 'download'])
    ->middleware('throttle:download')
    ->name('photos.download');

// Dashboard removed — users will use the public album as the home page

Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');

    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    // Appearance page removed
    
    // Admin routes
    Route::middleware(['admin'])->group(function () {
        Volt::route('settings/site', 'settings.site')->name('settings.site');
        Volt::route('settings/family-members', 'settings.family-members')->name('settings.family-members');
    });

    // Photo upload flow
    Route::get('photos/upload', [PhotoUploadController::class, 'showUploadForm'])->name('photos.upload.show');
    Route::post('photos/upload', [PhotoUploadController::class, 'handleUpload'])->name('photos.upload.handle');
    Route::post('photos/upload/async', [PhotoUploadController::class, 'handleUploadAsync'])->name('photos.upload.async');
    Route::get('photos/{photo}/crop', [PhotoUploadController::class, 'showCropForm'])->name('photos.crop.show');
    Route::post('photos/{photo}/crop', [PhotoUploadController::class, 'handleCrop'])->name('photos.crop.handle');

    // Post caption routes
    Route::get('posts/{post}/caption', [PhotoUploadController::class, 'showCaptionForm'])->name('posts.caption.show');
    Route::post('posts/{post}/caption', [PhotoUploadController::class, 'handleCaption'])->name('posts.caption.handle');

    // Edit and delete post (uploader or admin)
    Route::get('photo/{post}/edit', [PhotoController::class, 'edit'])->name('photos.edit');
    Route::patch('photo/{post}', [PhotoController::class, 'update'])->name('photos.update');
    Route::delete('photo/{post}', [PhotoController::class, 'destroy'])->name('photos.destroy');
});

// Public JSON feed for infinite scroll (guest throttled)
Route::get('photos/feed', [PhotoController::class, 'feed'])
    ->middleware('throttle:feed')
    ->name('photos.feed');

require __DIR__.'/auth.php';
