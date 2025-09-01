<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PhotoController extends Controller
{
    public function show(Photo $photo): View
    {
        $photo->load('user');

        $currentTs = ($photo->taken_at ?? $photo->created_at);

        // Previous = older than current
        $previous = Photo::where('is_completed', true)
            ->whereRaw('COALESCE(taken_at, created_at) < ?', [$currentTs])
            ->orderByRaw('COALESCE(taken_at, created_at) DESC')
            ->first();

        // Next = newer than current
        $next = Photo::where('is_completed', true)
            ->whereRaw('COALESCE(taken_at, created_at) > ?', [$currentTs])
            ->orderByRaw('COALESCE(taken_at, created_at) ASC')
            ->first();

        // Generate dynamic page title
        $title = 'Photo';
        if ($photo->caption) {
            // Truncate caption at 60 characters for title
            $truncatedCaption = strlen($photo->caption) > 60 
                ? substr($photo->caption, 0, 60) . '...' 
                : $photo->caption;
            $title = $truncatedCaption . ' - Photo';
        }

        return view('photos.show', [
            'photo' => $photo,
            'prevPhoto' => $previous,
            'nextPhoto' => $next,
            'title' => $title,
        ]);
    }

    public function download(Photo $photo)
    {
        $path = Storage::disk('public')->path($photo->original_path);
        return response()->download($path, basename($photo->original_path));
    }

    public function edit(Photo $photo): View
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->id === $photo->user_id), 403);

        $photo->load('user');

        return view('photos.edit', [
            'photo' => $photo,
        ]);
    }

    public function update(Request $request, Photo $photo): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->id === $photo->user_id), 403);

        $request->validate([
            'caption' => 'nullable|string|max:500',
        ]);

        $photo->update([
            'caption' => $request->input('caption'),
        ]);

        return redirect()->route('photos.show', $photo)->with('status', 'Caption updated!');
    }

    public function destroy(Photo $photo): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->id === $photo->user_id), 403);

        // Delete files from storage if present
        if ($photo->original_path) {
            Storage::disk('public')->delete($photo->original_path);
        }
        if ($photo->thumbnail_path) {
            Storage::disk('public')->delete($photo->thumbnail_path);
        }

        $photo->delete();

        return redirect()->route('home')->with('status', 'Photo deleted');
    }

    /**
     * JSON feed for infinite scroll on the album page
     */
    public function feed(Request $request): JsonResponse
    {
        $defaultPerPage = 20;
        $maxPerPage = (int) env('FEED_MAX_PER_PAGE', 50);
        $maxPage = (int) env('FEED_MAX_PAGE', 1000);

        $perPage = (int) ($request->integer('per_page') ?: $defaultPerPage);
        $perPage = max(1, min($perPage, $maxPerPage));

        $page = (int) ($request->integer('page') ?: 1);
        $page = max(1, min($page, $maxPage));

        $paginator = Photo::where('is_completed', true)
            ->orderByRaw('COALESCE(taken_at, created_at) DESC')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = $paginator->map(function (Photo $photo) {
            return [
                'id' => $photo->id,
                'url' => route('photos.show', $photo),
                'thumbnail_url' => $photo->thumbnail_url ?? $photo->original_url,
                'caption' => $photo->caption,
            ];
        });

        return response()->json([
            'data' => $items,
            'nextPage' => $paginator->hasMorePages() ? ($paginator->currentPage() + 1) : null,
        ]);
    }
}


