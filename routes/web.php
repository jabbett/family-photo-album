<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;
use App\Http\Controllers\PhotoUploadController;
use App\Http\Controllers\PhotoController;
use App\Models\Photo;

Route::get('/', function () {
    $perPage = 20;
    $paginator = Photo::where('is_completed', true)
        ->orderByRaw('COALESCE(taken_at, created_at) DESC')
        ->paginate($perPage);

    return view('photo-album', [
        'photos' => $paginator->items(),
        'nextPage' => $paginator->hasMorePages() ? ($paginator->currentPage() + 1) : null,
        'perPage' => $perPage,
    ]);
})->name('home');

// Public photo routes (guest throttled)
Route::get('photo/{photo}', [PhotoController::class, 'show'])
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
    Route::get('photos/{photo}/crop', [PhotoUploadController::class, 'showCropForm'])->name('photos.crop.show');
    Route::post('photos/{photo}/crop', [PhotoUploadController::class, 'handleCrop'])->name('photos.crop.handle');
    Route::get('photos/{photo}/caption', [PhotoUploadController::class, 'showCaptionForm'])->name('photos.caption.show');
    Route::post('photos/{photo}/caption', [PhotoUploadController::class, 'handleCaption'])->name('photos.caption.handle');

    // Delete photo (uploader or admin)
    Route::delete('photo/{photo}', [PhotoController::class, 'destroy'])->name('photos.destroy');
});

// Public JSON feed for infinite scroll (guest throttled)
Route::get('photos/feed', [PhotoController::class, 'feed'])
    ->middleware('throttle:feed')
    ->name('photos.feed');

require __DIR__.'/auth.php';
