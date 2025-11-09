<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;

class ImageOptimizationService
{
    /**
     * Optimize selfie image for storage (returns JPEG binary string)
     */
    public static function optimizeSelfie(UploadedFile $uploadedFile): string
    {
        $image = self::createImageFromFile($uploadedFile);
        $image = self::orientImageIfNeeded($image, $uploadedFile);

        $maxWidth = 800;
        $width = imagesx($image);
        if ($width > $maxWidth) {
            $image = self::resizeImage($image, $maxWidth);
        }

        return self::encodeJpeg($image, 70);
    }

    /**
     * Generate thumbnail for selfie (square 150x150)
     */
    public static function generateThumbnail(string $imagePath): ?string
    {
        try {
            $fullPath = storage_path('app/public/' . $imagePath);
            if (!file_exists($fullPath)) {
                return null;
            }

            $image = self::createImageFromContents(file_get_contents($fullPath));
            $thumbnail = self::createSquareThumbnail($image, 150);
            $thumbnailData = self::encodeJpeg($thumbnail, 60);

            $thumbnailPath = str_replace('selfies/', 'selfies/thumbnails/', $imagePath);
            Storage::put('public/' . $thumbnailPath, $thumbnailData);

            return $thumbnailPath;
        } catch (\Exception $e) {
            \Log::error('Failed to generate thumbnail: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Store optimized selfie and return path
     */
    public static function storeSelfie(UploadedFile $uploadedFile, string $type = 'checkin'): array
    {
        try {
            // Optimize image
            $optimizedImage = self::optimizeSelfie($uploadedFile);

            // Generate filename
            $filename = $type . '_' . time() . '_' . auth()->id() . '.jpg';
            $path = 'selfies/' . $filename;

            // Ensure directories exist
            Storage::makeDirectory('public/selfies');
            Storage::makeDirectory('public/selfies/thumbnails');

            // Store optimized image
            Storage::put('public/' . $path, $optimizedImage);

            // Generate thumbnail
            $thumbnailPath = self::generateThumbnail($path);

            // Calculate file size
            $fileSize = Storage::size('public/' . $path);

            return [
                'path' => $path,
                'thumbnail_path' => $thumbnailPath,
                'file_size' => $fileSize,
                'url' => Storage::url($path),
                'thumbnail_url' => $thumbnailPath ? Storage::url($thumbnailPath) : null,
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to store selfie: ' . $e->getMessage());
            throw new \Exception('Failed to process selfie image');
        }
    }
    
    /**
     * Validate selfie image
     */
    public static function validateSelfie(UploadedFile $uploadedFile): array
    {
        $errors = [];
        
        // Check file size (max 2MB)
        if ($uploadedFile->getSize() > 2048 * 1024) {
            $errors[] = 'Image size must be less than 2MB';
        }
        
        // Check image dimensions (minimum 300x300)
        try {
            $imageInfo = getimagesize($uploadedFile->getPathname());
            if ($imageInfo === false) {
                $errors[] = 'Invalid image file';
            } else {
                [$width, $height] = $imageInfo;
                if ($width < 300 || $height < 300) {
                    $errors[] = 'Image must be at least 300x300 pixels';
                }
                
                // Check aspect ratio (not too extreme)
                $aspectRatio = $width / $height;
                if ($aspectRatio < 0.5 || $aspectRatio > 2.0) {
                    $errors[] = 'Image aspect ratio is too extreme';
                }
            }
        } catch (\Exception $e) {
            $errors[] = 'Failed to validate image dimensions';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors,
        ];
    }
    
    /**
     * Delete selfie and thumbnail
     */
    public static function deleteSelfie(?string $path): bool
    {
        if (!$path) {
            return true;
        }
        
        try {
            $deleted = Storage::delete('public/' . $path);
            
            // Delete thumbnail if exists
            $thumbnailPath = str_replace('selfies/', 'selfies/thumbnails/', $path);
            Storage::delete('public/' . $thumbnailPath);
            
            return $deleted;
        } catch (\Exception $e) {
            \Log::error('Failed to delete selfie: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get storage usage statistics
     */
    public static function getStorageStats(): array
    {
        try {
            $selfiePath = storage_path('app/public/selfies');
            $totalSize = 0;
            $fileCount = 0;
            
            if (is_dir($selfiePath)) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($selfiePath),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );
                
                foreach ($iterator as $file) {
                    if ($file->isFile()) {
                        $totalSize += $file->getSize();
                        $fileCount++;
                    }
                }
            }
            
            return [
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'file_count' => $fileCount,
                'average_size_kb' => $fileCount > 0 ? round($totalSize / $fileCount / 1024, 2) : 0,
            ];
        } catch (\Exception $e) {
            \Log::error('Failed to get storage stats: ' . $e->getMessage());
            return [
                'total_size_mb' => 0,
                'file_count' => 0,
                'average_size_kb' => 0,
            ];
        }
    }
    /**
     * Helpers
     */
    protected static function createImageFromFile(UploadedFile $uploadedFile)
    {
        return self::createImageFromContents(file_get_contents($uploadedFile->getPathname()));
    }

    protected static function createImageFromContents(string $contents)
    {
        $image = @imagecreatefromstring($contents);
        if (!$image) {
            throw new \RuntimeException('Failed to create image resource');
        }
        return $image;
    }

    protected static function orientImageIfNeeded($image, UploadedFile $uploadedFile)
    {
        if (!function_exists('exif_read_data')) {
            return $image;
        }

        $extension = strtolower($uploadedFile->getClientOriginalExtension());
        if (!in_array($extension, ['jpg', 'jpeg'])) {
            return $image;
        }

        $exif = @exif_read_data($uploadedFile->getPathname());
        if (!$exif || !isset($exif['Orientation'])) {
            return $image;
        }

        switch ($exif['Orientation']) {
            case 3:
                $rotated = imagerotate($image, 180, 0);
                imagedestroy($image);
                return $rotated;
            case 6:
                $rotated = imagerotate($image, -90, 0);
                imagedestroy($image);
                return $rotated;
            case 8:
                $rotated = imagerotate($image, 90, 0);
                imagedestroy($image);
                return $rotated;
            default:
                return $image;
        }
    }

    protected static function resizeImage($image, int $maxWidth)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $ratio = $height / $width;
        $newWidth = $maxWidth;
        $newHeight = (int) round($maxWidth * $ratio);

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    protected static function encodeJpeg($image, int $quality): string
    {
        ob_start();
        imagejpeg($image, null, $quality);
        $data = ob_get_clean();
        imagedestroy($image);
        return $data;
    }

    protected static function createSquareThumbnail($image, int $size)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $shorter = min($width, $height);
        $srcX = (int) (($width - $shorter) / 2);
        $srcY = (int) (($height - $shorter) / 2);

        $thumbnail = imagecreatetruecolor($size, $size);
        imagecopyresampled(
            $thumbnail,
            $image,
            0,
            0,
            $srcX,
            $srcY,
            $size,
            $size,
            $shorter,
            $shorter
        );
        imagedestroy($image);

        return $thumbnail;
    }
}
