<?php

namespace App\Support;

use RuntimeException;

/**
 * Shrinks phone photos with GD (always available on shared hosting) so
 * handwriting samples stay small on disk and quick to load.
 */
class ImageResizer
{
    /** Returns JPEG bytes no wider or taller than $maxSide, with EXIF rotation applied. */
    public static function toJpeg(string $sourcePath, int $maxSide, int $quality = 82): string
    {
        $image = @imagecreatefromstring((string) file_get_contents($sourcePath));

        if ($image === false) {
            throw new RuntimeException('Unsupported image.');
        }

        $image = self::applyOrientation($image, $sourcePath);

        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $maxSide / max($width, $height));

        if ($scale < 1) {
            $resized = imagescale($image, (int) round($width * $scale), (int) round($height * $scale), IMG_BICUBIC);
            imagedestroy($image);
            $image = $resized;
        }

        // Flatten transparency (PNG/WebP) onto white so it isn't black in the JPEG.
        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
        imagedestroy($image);

        ob_start();
        imagejpeg($canvas, null, $quality);
        imagedestroy($canvas);

        return (string) ob_get_clean();
    }

    private static function applyOrientation(\GdImage $image, string $path): \GdImage
    {
        if (! function_exists('exif_read_data')) {
            return $image;
        }

        $orientation = (@exif_read_data($path) ?: [])['Orientation'] ?? 1;

        return match ((int) $orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => $image,
        };
    }
}
