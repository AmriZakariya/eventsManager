<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Shrinks and recompresses uploaded images in place with GD, so the app
 * downloads lighter files. The file keeps its name and format (URLs do not
 * change) and is only replaced when the result is smaller.
 */
class ImageOptimizer
{
    /** Longest side after resizing: sharp full-screen on phones, floor plan included. */
    public const MAX_DIMENSION = 2048;

    public const JPEG_QUALITY = 82;
    public const WEBP_QUALITY = 82;

    /**
     * Replace the file only when it gets at least this much smaller, so an
     * already-optimized JPEG is not re-encoded (and degraded) on every run.
     */
    public const MIN_GAIN = 0.10;

    private const TYPES = [
        IMAGETYPE_JPEG => 'jpeg',
        IMAGETYPE_PNG  => 'png',
        IMAGETYPE_WEBP => 'webp',
    ];

    public static function available(): bool
    {
        return extension_loaded('gd') && function_exists('imagecreatefromjpeg');
    }

    /**
     * Optimizes the file at $path in place.
     *
     * @return array{before: int, after: int, width: int, height: int}|null
     *         null when the file is not a supported image or nothing was gained.
     */
    public static function optimize(string $path, bool $dryRun = false): ?array
    {
        if (! self::available() || ! is_file($path)) {
            return null;
        }

        $info = @getimagesize($path);
        $type = $info ? (self::TYPES[$info[2]] ?? null) : null;
        if ($type === null) {
            return null; // GIF, SVG, PDF, ... left untouched
        }

        $before = filesize($path);
        $tmp = $path . '.optimizing';

        try {
            $image = match ($type) {
                'jpeg' => @imagecreatefromjpeg($path),
                'png'  => @imagecreatefrompng($path),
                'webp' => @imagecreatefromwebp($path),
            };

            if (! $image) {
                return null;
            }

            if ($type === 'jpeg') {
                $image = self::applyExifOrientation($image, $path);
            }

            $image = self::resize($image, $type);

            $written = match ($type) {
                'jpeg' => (function () use ($image, $tmp) {
                    imageinterlace($image, true); // progressive: shows early on slow networks
                    return imagejpeg($image, $tmp, self::JPEG_QUALITY);
                })(),
                'png'  => imagepng($image, $tmp, 9),
                'webp' => imagewebp($image, $tmp, self::WEBP_QUALITY),
            };

            $width = imagesx($image);
            $height = imagesy($image);
            imagedestroy($image);

            if (! $written || ! is_file($tmp)) {
                return null;
            }

            $after = filesize($tmp);

            // Keep the original when re-encoding did not help (already optimized).
            if ($after > $before * (1 - self::MIN_GAIN)) {
                return null;
            }

            if ($dryRun) {
                return ['before' => $before, 'after' => $after, 'width' => $width, 'height' => $height];
            }

            if (! @rename($tmp, $path)) {
                return null;
            }

            return ['before' => $before, 'after' => $after, 'width' => $width, 'height' => $height];
        } catch (\Throwable $e) {
            Log::warning('Image optimization failed.', ['path' => $path, 'error' => $e->getMessage()]);

            return null;
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }
    }

    private static function resize(\GdImage $image, string $type): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $longest = max($width, $height);

        if ($longest <= self::MAX_DIMENSION) {
            return self::keepAlpha($image, $type);
        }

        $scale = self::MAX_DIMENSION / $longest;
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $resized = imagecreatetruecolor($newWidth, $newHeight);
        self::keepAlpha($resized, $type);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        imagedestroy($image);

        return $resized;
    }

    /** PNG and WebP keep their transparency. */
    private static function keepAlpha(\GdImage $image, string $type): \GdImage
    {
        if ($type !== 'jpeg') {
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        return $image;
    }

    /** Phone photos are often stored sideways with a rotation flag; apply it. */
    private static function applyExifOrientation(\GdImage $image, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (int) (@exif_read_data($path)['Orientation'] ?? 1);

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated) {
            imagedestroy($image);

            return $rotated;
        }

        return $image;
    }
}
