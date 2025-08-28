<?php

namespace App\Http\Controllers;

use App\Models\Photo;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PhotoUploadController extends Controller
{
    public function showUploadForm(): View
    {
        return view('photos.upload');
    }

    public function handleUpload(Request $request): RedirectResponse
    {
        // Proactive check: if PHP rejected the file (e.g., size > upload_max_filesize),
        // validation will show a generic message. Provide a clearer one with limits.
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            if (!$file->isValid()) {
                $limits = sprintf(
                    'upload_max_filesize=%s, post_max_size=%s',
                    ini_get('upload_max_filesize') ?: 'unknown',
                    ini_get('post_max_size') ?: 'unknown'
                );

                $errorCode = method_exists($file, 'getError') ? $file->getError() : null;
                $errorMessage = $this->translateUploadErrorCode($errorCode, $limits);

                logger()->warning('Photo upload failed', [
                    'error_code' => $errorCode,
                    'limits' => $limits,
                    'content_length' => request()->server('CONTENT_LENGTH'),
                ]);

                return back()->withErrors(['photo' => $errorMessage])->withInput();
            }
        } else if ($request->isMethod('post') && (int) ($request->server('CONTENT_LENGTH') ?? 0) > 0 && empty($_POST)) {
            // Classic signature of post_max_size overflow: non-empty CONTENT_LENGTH but empty $_POST/$_FILES
            $limits = sprintf(
                'upload_max_filesize=%s, post_max_size=%s',
                ini_get('upload_max_filesize') ?: 'unknown',
                ini_get('post_max_size') ?: 'unknown'
            );
            $message = "The photo failed to upload because the request exceeded PHP's post_max_size. Current limits: {$limits}.";
            logger()->warning('Photo upload failed due to post_max_size', [
                'limits' => $limits,
                'content_length' => request()->server('CONTENT_LENGTH'),
            ]);
            return back()->withErrors(['photo' => $message])->withInput();
        }

        $validated = $request->validate([
            'photo' => ['required', 'image', 'max:10240'], // up to 10MB (10,240 KB)
        ]);

        $file = $validated['photo'];

        // Extract taken_at from the uploaded file before sanitizing
        $takenAt = null;
        if (function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($file->getRealPath(), 'EXIF');
                if ($exif && !empty($exif['DateTimeOriginal'])) {
                    $takenAt = date('Y-m-d H:i:s', strtotime($exif['DateTimeOriginal']));
                }
            } catch (\Throwable $e) {
                // Ignore EXIF extraction errors
            }
        }

        // Sanitize and store the original (re-encode without metadata)
        [$originalPath, $width, $height] = $this->sanitizeAndStoreOriginal($file->getRealPath());

        $photo = Photo::create([
            'user_id' => Auth::id(),
            'original_path' => $originalPath,
            'width' => $width,
            'height' => $height,
            'taken_at' => $takenAt,
        ]);

        if ($width === $height) {
            // Create centered square thumbnail automatically
            $this->createSquareThumbnail($photo, 'center');
            return redirect()->route('photos.caption.show', $photo);
        }

        return redirect()->route('photos.crop.show', $photo);
    }

    public function showCropForm(Photo $photo): View
    {
        $this->authorizeOwner($photo);
        return view('photos.crop', compact('photo'));
    }

    public function handleCrop(Request $request, Photo $photo): RedirectResponse
    {
        $this->authorizeOwner($photo);
        // If JS cropper was used, we will get explicit coordinates.
        $validated = $request->validate([
            'anchor' => ['nullable', 'in:center,top,bottom,left,right'],
            'crop_x' => ['nullable', 'integer', 'min:0'],
            'crop_y' => ['nullable', 'integer', 'min:0'],
            'crop_size' => ['nullable', 'integer', 'min:1'],
        ]);

        if (isset($validated['crop_x'], $validated['crop_y'], $validated['crop_size'])) {
            $this->createSquareThumbnailWithCoords($photo, (int) $validated['crop_x'], (int) $validated['crop_y'], (int) $validated['crop_size']);
        } else {
            $anchor = $validated['anchor'] ?? 'center';
            $this->createSquareThumbnail($photo, $anchor);
        }

        return redirect()->route('photos.caption.show', $photo);
    }

    public function showCaptionForm(Photo $photo): View
    {
        $this->authorizeOwner($photo);
        return view('photos.caption', compact('photo'));
    }

    public function handleCaption(Request $request, Photo $photo): RedirectResponse
    {
        $this->authorizeOwner($photo);

        $validated = $request->validate([
            'caption' => ['nullable', 'string', 'max:500'],
        ]);

        $photo->caption = $validated['caption'] ?? null;
        $photo->is_completed = true;
        $photo->save();

        return redirect()->route('home')->with('status', 'Photo uploaded');
    }

    protected function authorizeOwner(Photo $photo): void
    {
        abort_unless($photo->user_id === Auth::id(), 403);
    }

    protected function createSquareThumbnail(Photo $photo, string $anchor): void
    {
        $absolutePath = Storage::disk('public')->path($photo->original_path);
        [$width, $height, $type] = getimagesize($absolutePath);

        $image = $this->createImageResource($absolutePath, $type);
        if (!$image) {
            throw new \RuntimeException('Unsupported image type');
        }

        $squareSize = min($width, $height);

        $x = (int) floor(($width - $squareSize) / 2);
        $y = (int) floor(($height - $squareSize) / 2);

        if ($width > $height) {
            // Landscape: move horizontally for left/right, vertically for top/bottom stays centered vertically
            if ($anchor === 'left') {
                $x = 0;
            } elseif ($anchor === 'right') {
                $x = $width - $squareSize;
            }
        } elseif ($height > $width) {
            // Portrait: move vertically for top/bottom, horizontally for left/right stays centered horizontally
            if ($anchor === 'top') {
                $y = 0;
            } elseif ($anchor === 'bottom') {
                $y = $height - $squareSize;
            }
        } else {
            // Already square; keep center
        }

        $src = imagecreatetruecolor($squareSize, $squareSize);
        imagecopyresampled($src, $image, 0, 0, $x, $y, $squareSize, $squareSize, $squareSize, $squareSize);

        $thumbSize = 800; // produce a reasonable display-size square
        $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $thumbSize, $thumbSize, $squareSize, $squareSize);

        $filename = 'photos/thumbnails/' . Str::uuid()->toString() . '.jpg';
        // Ensure directory exists
        Storage::disk('public')->makeDirectory(dirname($filename));
        $thumbPath = Storage::disk('public')->path($filename);

        imagejpeg($thumb, $thumbPath, 85);
        imagedestroy($src);
        imagedestroy($thumb);
        imagedestroy($image);

        $photo->thumbnail_path = $filename;
        $photo->save();
    }

    protected function createSquareThumbnailWithCoords(Photo $photo, int $x, int $y, int $size): void
    {
        $absolutePath = Storage::disk('public')->path($photo->original_path);
        [$width, $height, $type] = getimagesize($absolutePath);
        $image = $this->createImageResource($absolutePath, $type);
        if (!$image) {
            throw new \RuntimeException('Unsupported image type');
        }

        $size = max(1, $size);
        $x = max(0, min($x, $width - 1));
        $y = max(0, min($y, $height - 1));
        $size = min($size, $width - $x, $height - $y);

        $src = imagecreatetruecolor($size, $size);
        imagecopyresampled($src, $image, 0, 0, $x, $y, $size, $size, $size, $size);

        $thumbSize = 800;
        $thumb = imagecreatetruecolor($thumbSize, $thumbSize);
        imagecopyresampled($thumb, $src, 0, 0, 0, 0, $thumbSize, $thumbSize, $size, $size);

        $filename = 'photos/thumbnails/' . Str::uuid()->toString() . '.jpg';
        // Ensure directory exists
        Storage::disk('public')->makeDirectory(dirname($filename));
        $thumbPath = Storage::disk('public')->path($filename);
        imagejpeg($thumb, $thumbPath, 85);

        imagedestroy($src);
        imagedestroy($thumb);
        imagedestroy($image);

        $photo->thumbnail_path = $filename;
        $photo->save();
    }

    protected function createImageResource(string $path, int $imageType)
    {
        switch ($imageType) {
            case IMAGETYPE_JPEG:
                return imagecreatefromjpeg($path);
            case IMAGETYPE_PNG:
                $img = imagecreatefrompng($path);
                imagealphablending($img, true);
                imagesavealpha($img, true);
                return $img;
            case IMAGETYPE_GIF:
                return imagecreatefromgif($path);
            default:
                return null;
        }
    }

    /**
     * Re-encode the uploaded image to strip metadata and store as the canonical original.
     * Returns [relativePath, width, height].
     */
    protected function sanitizeAndStoreOriginal(string $uploadedTempPath): array
    {
        [$width, $height, $type] = getimagesize($uploadedTempPath);
        $image = $this->createImageResource($uploadedTempPath, $type);
        if (!$image) {
            throw new \RuntimeException('Unsupported image type');
        }

        // Determine destination extension and encoder
        $extension = match ($type) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            default => 'jpg',
        };

        $relativePath = 'photos/originals/' . Str::uuid()->toString() . '.' . $extension;
        Storage::disk('public')->makeDirectory('photos/originals');
        $dest = Storage::disk('public')->path($relativePath);

        // Re-encode without carrying over any metadata
        switch ($extension) {
            case 'jpg':
                imagejpeg($image, $dest, 90);
                break;
            case 'png':
                // 0 (no compression) - 9; use 6 as a balance
                imagepng($image, $dest, 6);
                break;
            case 'gif':
                imagegif($image, $dest);
                break;
        }

        imagedestroy($image);

        // Confirm dimensions from resource
        $finalWidth = imagesx(imagecreatefromstring(file_get_contents($dest)));
        $finalHeight = imagesy(imagecreatefromstring(file_get_contents($dest)));

        return [$relativePath, $finalWidth, $finalHeight];
    }

    protected function translateUploadErrorCode(?int $code, string $limits): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds upload_max_filesize. Current limits: {$limits}.",
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded. Please choose a photo.',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder on the server.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk (tmp directory).',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload.',
            default => "The photo failed to upload. Server limits: {$limits}. Try a smaller photo or raise limits.",
        };
    }
}


