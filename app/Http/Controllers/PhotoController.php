<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class PhotoController extends Controller
{
    public function show(Post $post): View
    {
        $post->load(['photos', 'user']);

        $currentTs = ($post->display_date ?? $post->created_at);

        // Previous = older than current
        $previous = Post::where('is_completed', true)
            ->whereRaw('COALESCE(display_date, created_at) < ?', [$currentTs])
            ->orderByRaw('COALESCE(display_date, created_at) DESC')
            ->first();

        // Next = newer than current
        $next = Post::where('is_completed', true)
            ->whereRaw('COALESCE(display_date, created_at) > ?', [$currentTs])
            ->orderByRaw('COALESCE(display_date, created_at) ASC')
            ->first();

        // Generate dynamic page title
        $title = $post->photos->count() === 1 ? 'Photo' : $post->photos->count() . ' Photos';
        if ($post->caption) {
            // Truncate caption at 60 characters for title
            $truncatedCaption = strlen($post->caption) > 60
                ? substr($post->caption, 0, 60) . '...'
                : $post->caption;
            $title = $truncatedCaption . ' - ' . $title;
        }

        return view('photos.show', [
            'post' => $post,
            'prevPost' => $previous,
            'nextPost' => $next,
            'title' => $title,
        ]);
    }

    public function download(Photo $photo)
    {
        $path = Storage::disk('public')->path($photo->original_path);
        return response()->download($path, basename($photo->original_path));
    }

    public function edit(Post $post): View
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->id === $post->user_id), 403);

        $post->load(['photos', 'user']);

        return view('photos.edit', [
            'post' => $post,
        ]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->id === $post->user_id), 403);

        $request->validate([
            'caption' => 'nullable|string|max:2000',
            'taken_date' => 'required|date',
            'taken_time' => 'required|date_format:H:i',
        ]);

        // Always combine date and time fields into display_date
        $displayDate = $request->input('taken_date') . ' ' . $request->input('taken_time') . ':00';

        $updateData = [
            'caption' => $request->input('caption'),
            'display_date' => $displayDate,
        ];

        $post->update($updateData);

        return redirect()->route('photos.show', $post)->with('status', 'Post updated!');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $user = Auth::user();
        abort_unless($user && ($user->isAdmin() || $user->id === $post->user_id), 403);

        // Delete all photo files from storage
        foreach ($post->photos as $photo) {
            if ($photo->original_path) {
                Storage::disk('public')->delete($photo->original_path);
            }
            if ($photo->thumbnail_path) {
                Storage::disk('public')->delete($photo->thumbnail_path);
            }
        }

        // Delete post (cascade deletes photos)
        $post->delete();

        return redirect()->route('home')->with('status', 'Post deleted');
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

        $paginator = Post::with(['photos' => function($query) {
                $query->where('position', 0); // Load cover photo only
            }])
            ->withCount('photos') // Add photo count for collection indicator
            ->where('is_completed', true)
            ->orderByRaw('COALESCE(display_date, created_at) DESC')
            ->paginate($perPage, ['*'], 'page', $page);

        $items = $paginator->map(function (Post $post) {
            $coverPhoto = $post->photos->first();
            return [
                'id' => $post->id,
                'url' => route('photos.show', $post),
                'thumbnail_url' => $coverPhoto?->thumbnail_url ?? $coverPhoto?->original_url,
                'caption' => $post->caption,
                'is_collection' => $post->photos_count > 1,
            ];
        });

        return response()->json([
            'data' => $items,
            'nextPage' => $paginator->hasMorePages() ? ($paginator->currentPage() + 1) : null,
        ]);
    }
}


