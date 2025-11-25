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
use Illuminate\Http\JsonResponse;

class PhotoUploadController extends Controller
{
    public function showUploadForm(): View
    {
        return view('photos.upload');
    }

    /**
     * Handle async photo upload via fetch API
     * Returns JSON with photo ID for later caption submission
     */
    public function handleUploadAsync(Request $request): JsonResponse
    {
        logger()->info('Async upload attempt started', [
            'user_id' => Auth::id(),
            'has_file' => $request->hasFile('photo'),
        ]);

        // Reuse existing validation logic
        if ($request->hasFile('photo')) {
            $file = $request->file('photo');
            if (!$file->isValid()) {
                $limits = sprintf(
                    'upload_max_filesize=%s, post_max_size=%s',
                    ini_get('upload_max_filesize') ?: 'unknown',
                    ini_get('post_max_size') ?: 'unknown'
                );
                $errorMessage = $this->translateUploadErrorCode($file->getError(), $limits);

                logger()->warning('Async upload failed - invalid file', [
                    'error' => $errorMessage,
                    'user_id' => Auth::id(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 422);
            }
        } else if ($request->isMethod('post') && (int) ($request->server('CONTENT_LENGTH') ?? 0) > 0 && empty($_POST)) {
            // Classic signature of post_max_size overflow
            $limits = sprintf(
                'upload_max_filesize=%s, post_max_size=%s',
                ini_get('upload_max_filesize') ?: 'unknown',
                ini_get('post_max_size') ?: 'unknown'
            );
            $message = "The photo failed to upload because the request exceeded PHP's post_max_size. Current limits: {$limits}.";

            logger()->warning('Async upload failed - post_max_size exceeded', [
                'limits' => $limits,
                'content_length' => $request->server('CONTENT_LENGTH'),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $message
            ], 422);
        }

        try {
            $validated = $request->validate([
                'photo' => [
                    'required',
                    'file',
                    'mimes:jpeg,jpg,png,gif,heic,heif',
                    'max:10240'
                ],
            ]);

            $file = $validated['photo'];

            // Extract taken_at from EXIF
            $takenAt = $this->extractTakenAtDate($file->getRealPath());

            // Store original
            [$originalPath, $width, $height] = $this->storeOriginal($file->getRealPath());

            // Create Photo record (incomplete, no thumbnail yet)
            $photo = Photo::create([
                'user_id' => Auth::id(),
                'original_path' => $originalPath,
                'width' => $width,
                'height' => $height,
                'taken_at' => $takenAt,
                'is_completed' => false, // Explicitly incomplete
            ]);

            logger()->info('Async upload completed', [
                'photo_id' => $photo->id,
                'user_id' => Auth::id(),
                'width' => $width,
                'height' => $height,
            ]);

            return response()->json([
                'success' => true,
                'photo_id' => $photo->id,
                'width' => $width,
                'height' => $height,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            logger()->warning('Async upload validation failed', [
                'errors' => $e->errors(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);

        } catch (\Exception $e) {
            logger()->error('Async upload failed', [
                'error' => $e->getMessage(),
                'user_id' => Auth::id(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process photo: ' . $e->getMessage()
            ], 500);
        }
    }

    public function handleUpload(Request $request): RedirectResponse
    {
        logger()->info('Upload attempt started', [
            'user_id' => Auth::id(),
            'request_size' => $request->server('CONTENT_LENGTH'),
            'has_file' => $request->hasFile('photo'),
            'post_data_empty' => empty($_POST),
            'files_data_empty' => empty($_FILES),
            'files_debug' => $_FILES,
            'php_limits' => [
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'memory_limit' => ini_get('memory_limit'),
            ],
        ]);

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
            'photo' => [
                'required', 
                'file',
                'mimes:jpeg,jpg,png,gif,heic,heif', 
                'max:10240' // up to 10MB (10,240 KB)
            ],
        ]);

        $file = $validated['photo'];

        // Extract taken_at from the uploaded file before processing
        $takenAt = $this->extractTakenAtDate($file->getRealPath());

        // Store the original (preserving all metadata for now)
        [$originalPath, $width, $height] = $this->storeOriginal($file->getRealPath());

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

        // Check if this is the NEW flow (caption → crop) or OLD flow (crop → caption)
        // NEW flow: Photo has a caption already set → mark complete and go home
        // OLD flow: Photo has no caption → go to caption page
        if ($photo->caption !== null) {
            // NEW FLOW: Caption already saved, mark as completed
            $photo->is_completed = true;
            $photo->save();
            return redirect()->route('home')->with('status', 'Photo uploaded');
        } else {
            // OLD FLOW: Still need caption, redirect to caption page
            return redirect()->route('photos.caption.show', $photo);
        }
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

        // Save caption (photo still incomplete at this point)
        $photo->caption = $validated['caption'] ?? null;
        $photo->save();

        // Check if thumbnail already exists (OLD flow: crop → caption)
        // If thumbnail exists, just complete the photo
        if ($photo->thumbnail_path !== null) {
            $photo->is_completed = true;
            $photo->save();
            return redirect()->route('home')->with('status', 'Photo uploaded');
        }

        // NEW flow: Caption entered before crop
        // Check if square photo needs auto-crop or user crop
        if ($photo->width === $photo->height) {
            // Auto-crop square photos
            $this->createSquareThumbnail($photo, 'center');
            $photo->is_completed = true;
            $photo->save();
            return redirect()->route('home')->with('status', 'Photo uploaded');
        }

        // Non-square photos go to crop page (caption saved, but photo not completed yet)
        return redirect()->route('photos.crop.show', $photo);
    }

    protected function authorizeOwner(Photo $photo): void
    {
        abort_unless($photo->user_id === Auth::id(), 403);
    }

    protected function createSquareThumbnail(Photo $photo, string $anchor): void
    {
        $absolutePath = Storage::disk('public')->path($photo->original_path);
        $imagick = new \Imagick($absolutePath);
        
        // Auto-orient the image based on EXIF data (compatible version)
        $this->autoOrientImagick($imagick);
        
        $width = $imagick->getImageWidth();
        $height = $imagick->getImageHeight();
        $squareSize = min($width, $height);

        $x = (int) floor(($width - $squareSize) / 2);
        $y = (int) floor(($height - $squareSize) / 2);

        if ($width > $height) {
            // Landscape: move horizontally for left/right
            if ($anchor === 'left') {
                $x = 0;
            } elseif ($anchor === 'right') {
                $x = $width - $squareSize;
            }
        } elseif ($height > $width) {
            // Portrait: move vertically for top/bottom
            if ($anchor === 'top') {
                $y = 0;
            } elseif ($anchor === 'bottom') {
                $y = $height - $squareSize;
            }
        }

        // Crop to square
        $imagick->cropImage($squareSize, $squareSize, $x, $y);
        
        // Resize to thumbnail size
        $thumbSize = 800;
        $imagick->resizeImage($thumbSize, $thumbSize, \Imagick::FILTER_LANCZOS, 1);
        
        // Set format and quality
        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompressionQuality(85);
        $imagick->stripImage(); // Remove EXIF from thumbnail

        $filename = 'photos/thumbnails/' . Str::uuid()->toString() . '.jpg';
        Storage::disk('public')->makeDirectory(dirname($filename));
        $thumbPath = Storage::disk('public')->path($filename);
        
        $imagick->writeImage($thumbPath);
        $imagick->destroy();

        $photo->thumbnail_path = $filename;
        $photo->save();
    }


    protected function createSquareThumbnailWithCoords(Photo $photo, int $x, int $y, int $size): void
    {
        $absolutePath = Storage::disk('public')->path($photo->original_path);
        $imagick = new \Imagick($absolutePath);
        
        // Auto-orient the image based on EXIF data (compatible version)
        $this->autoOrientImagick($imagick);
        
        $width = $imagick->getImageWidth();
        $height = $imagick->getImageHeight();

        $size = max(1, $size);
        $x = max(0, min($x, $width - 1));
        $y = max(0, min($y, $height - 1));
        $size = min($size, $width - $x, $height - $y);

        // Crop to specified coordinates
        $imagick->cropImage($size, $size, $x, $y);
        
        // Resize to thumbnail size
        $thumbSize = 800;
        $imagick->resizeImage($thumbSize, $thumbSize, \Imagick::FILTER_LANCZOS, 1);
        
        // Set format and quality
        $imagick->setImageFormat('jpeg');
        $imagick->setImageCompressionQuality(85);
        $imagick->stripImage(); // Remove EXIF from thumbnail

        $filename = 'photos/thumbnails/' . Str::uuid()->toString() . '.jpg';
        Storage::disk('public')->makeDirectory(dirname($filename));
        $thumbPath = Storage::disk('public')->path($filename);
        
        $imagick->writeImage($thumbPath);
        $imagick->destroy();

        $photo->thumbnail_path = $filename;
        $photo->save();
    }


    /**
     * Store original image, converting HEIC to JPEG while preserving EXIF data.
     * Returns [relativePath, width, height].
     */
    protected function storeOriginal(string $uploadedTempPath): array
    {
        // Check if this is a HEIC file by trying to read with Imagick
        $isHeic = $this->isHeicFile($uploadedTempPath);
        
        if ($isHeic) {
            return $this->convertAndStoreHeic($uploadedTempPath);
        }
        
        // Handle regular image formats
        [$width, $height, $type] = getimagesize($uploadedTempPath);
        
        // Determine file extension
        $extension = match ($type) {
            IMAGETYPE_JPEG => 'jpg',
            IMAGETYPE_PNG => 'png',
            IMAGETYPE_GIF => 'gif',
            default => 'jpg',
        };

        // Generate destination path
        $relativePath = 'photos/originals/' . Str::uuid()->toString() . '.' . $extension;
        Storage::disk('public')->makeDirectory('photos/originals');
        $dest = Storage::disk('public')->path($relativePath);

        // Simply copy the file preserving all metadata
        if (!copy($uploadedTempPath, $dest)) {
            throw new \RuntimeException('Failed to store uploaded file');
        }

        return [$relativePath, $width, $height];
    }

    /**
     * Extract taken_at date from image file (supports both JPEG and HEIC)
     */
    protected function extractTakenAtDate(string $filePath): ?string
    {
        $takenAt = null;

        // Method 1: Try EXIF data (works for JPEG and some HEIC)
        if (function_exists('exif_read_data')) {
            try {
                $exif = @exif_read_data($filePath, 'EXIF');
                if ($exif && !empty($exif['DateTimeOriginal'])) {
                    $takenAt = date('Y-m-d H:i:s', strtotime($exif['DateTimeOriginal']));
                    logger()->info('Date extracted via EXIF', ['date' => $takenAt, 'file' => basename($filePath)]);
                }
            } catch (\Throwable $e) {
                // Ignore EXIF extraction errors
            }
        }

        // Method 2: Try Imagick for HEIC files (more comprehensive)
        if (!$takenAt) {
            try {
                $imagick = new \Imagick($filePath);
                
                // Try various EXIF date properties that HEIC files might use
                $dateProperties = [
                    'exif:DateTimeOriginal',
                    'exif:DateTime', 
                    'exif:CreateDate',
                    'date:create',
                    'date:modify',
                ];

                foreach ($dateProperties as $property) {
                    try {
                        $dateValue = $imagick->getImageProperty($property);
                        if ($dateValue && $dateValue !== '') {
                            // Parse the date - handle various formats
                            $timestamp = strtotime($dateValue);
                            if ($timestamp !== false) {
                                $takenAt = date('Y-m-d H:i:s', $timestamp);
                                logger()->info('Date extracted via Imagick', [
                                    'property' => $property,
                                    'raw_value' => $dateValue,
                                    'parsed_date' => $takenAt,
                                    'file' => basename($filePath)
                                ]);
                                break;
                            }
                        }
                    } catch (\Exception $e) {
                        // Continue to next property
                    }
                }

                $imagick->destroy();
            } catch (\Exception $e) {
                logger()->warning('Failed to extract date with Imagick', [
                    'error' => $e->getMessage(),
                    'file' => basename($filePath)
                ]);
            }
        }

        // Method 3: Fallback to file modification time if no EXIF date found
        if (!$takenAt) {
            $fileTime = filemtime($filePath);
            if ($fileTime !== false) {
                $takenAt = date('Y-m-d H:i:s', $fileTime);
                logger()->info('Date extracted from file timestamp (fallback)', [
                    'date' => $takenAt,
                    'file' => basename($filePath)
                ]);
            }
        }

        return $takenAt;
    }

    /**
     * Check if file is HEIC format
     */
    protected function isHeicFile(string $filePath): bool
    {
        // First check file extension
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $isHeicExtension = in_array($extension, ['heic', 'heif']);
        
        // Try to read MIME type
        $mimeType = mime_content_type($filePath);
        $isHeicMime = in_array($mimeType, ['image/heic', 'image/heif']);
        
        // Try Imagick format detection
        $isHeicFormat = false;
        try {
            $imagick = new \Imagick($filePath);
            $format = strtoupper($imagick->getImageFormat());
            $imagick->destroy();
            $isHeicFormat = in_array($format, ['HEIC', 'HEIF']);
        } catch (\Exception $e) {
            logger()->warning('Failed to detect format with Imagick', [
                'error' => $e->getMessage(),
                'file_path' => $filePath,
            ]);
        }
        
        $isHeic = $isHeicExtension || $isHeicMime || $isHeicFormat;
        
        logger()->info('HEIC file detection', [
            'file_path' => $filePath,
            'extension' => $extension,
            'mime_type' => $mimeType,
            'extension_match' => $isHeicExtension,
            'mime_match' => $isHeicMime,
            'format_match' => $isHeicFormat,
            'is_heic' => $isHeic,
        ]);
        
        return $isHeic;
    }

    /**
     * Convert HEIC to JPEG and store, preserving EXIF data
     */
    protected function convertAndStoreHeic(string $uploadedTempPath): array
    {
        logger()->info('Converting HEIC file to JPEG', [
            'file_path' => $uploadedTempPath,
            'file_size' => filesize($uploadedTempPath),
        ]);

        try {
            $imagick = new \Imagick($uploadedTempPath);
            
            // Get dimensions before conversion
            $width = $imagick->getImageWidth();
            $height = $imagick->getImageHeight();
            
            // Validate dimensions
            if (!$width || !$height) {
                logger()->error('Invalid image dimensions from HEIC file', [
                    'width' => $width,
                    'height' => $height,
                    'file_path' => $uploadedTempPath,
                ]);
                throw new \RuntimeException('Invalid image dimensions detected');
            }
            
            // Convert to JPEG format
            $imagick->setImageFormat('JPEG');
            $imagick->setImageCompressionQuality(90);

            // Generate destination path (always .jpg for converted HEIC)
            $relativePath = 'photos/originals/' . Str::uuid()->toString() . '.jpg';
            Storage::disk('public')->makeDirectory('photos/originals');
            $dest = Storage::disk('public')->path($relativePath);
            
            // Write converted image
            $imagick->writeImage($dest);
            $imagick->destroy();

            // Verify the conversion worked
            if (!file_exists($dest) || filesize($dest) === 0) {
                logger()->error('HEIC conversion failed - output file invalid', [
                    'dest' => $dest,
                    'exists' => file_exists($dest),
                    'size' => file_exists($dest) ? filesize($dest) : 'N/A',
                ]);
                throw new \RuntimeException('HEIC conversion failed');
            }

            logger()->info('HEIC conversion completed successfully', [
                'original_size' => filesize($uploadedTempPath),
                'converted_size' => filesize($dest),
                'width' => $width,
                'height' => $height,
                'dest_path' => $relativePath,
            ]);

            return [$relativePath, $width, $height];
            
        } catch (\Exception $e) {
            logger()->error('HEIC conversion error', [
                'error' => $e->getMessage(),
                'file_path' => $uploadedTempPath,
            ]);
            
            // Check if this is a "no decode delegate" error (HEIC support not available)
            if (str_contains($e->getMessage(), 'NoDecodeDelegateForThisImageFormat')) {
                throw new \RuntimeException('HEIC files are not supported on this server. Please convert to JPEG first or install HEIC support (libheif).');
            }
            
            throw new \RuntimeException('Failed to convert HEIC file: ' . $e->getMessage());
        }
    }

    /**
     * Auto-orient Imagick image based on EXIF orientation (compatible version)
     */
    protected function autoOrientImagick(\Imagick $imagick): void
    {
        // Check if the newer method exists first
        if (method_exists($imagick, 'autoOrientImage')) {
            $imagick->autoOrientImage();
            return;
        }

        // Fallback: manual orientation handling
        try {
            $orientation = $imagick->getImageProperty('exif:Orientation');
            
            if (!$orientation || $orientation == 1) {
                return; // No orientation or already correct
            }

            switch ($orientation) {
                case 2:
                    $imagick->flopImage();
                    break;
                case 3:
                    $imagick->rotateImage('transparent', 180);
                    break;
                case 4:
                    $imagick->flipImage();
                    break;
                case 5:
                    $imagick->flipImage();
                    $imagick->rotateImage('transparent', -90);
                    break;
                case 6:
                    $imagick->rotateImage('transparent', 90);
                    break;
                case 7:
                    $imagick->flopImage();
                    $imagick->rotateImage('transparent', -90);
                    break;
                case 8:
                    $imagick->rotateImage('transparent', -90);
                    break;
            }
            
            // Reset orientation to normal after applying rotation
            $imagick->setImageProperty('exif:Orientation', '1');
        } catch (\Exception $e) {
            // Ignore orientation errors
        }
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


