<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Normalises a visitor-supplied photo before it is stored.
 *
 * Phone photos arrive with GPS coordinates, device model and a rotation flag.
 * The pipeline is deliberately ordered: orientation is read and APPLIED first,
 * because stripping the metadata is what discards the flag — strip first and
 * every portrait photo lands sideways. Re-encoding through GD is what removes
 * the metadata; GD carries no EXIF through to its output.
 *
 * Output is always WebP on the private 'local' disk (storage/app/private),
 * which has no URL and no symlink into public/.
 */
class PhotoStorage
{
    public const DISK = 'local';

    public const DIRECTORY = 'registrations';

    protected const MAX_EDGE = 1600;

    protected const QUALITY = 82;

    /** Returns the stored relative path, e.g. "registrations/<uuid>.webp". */
    public function store(UploadedFile $file, string $uuid): string
    {
        $image = @imagecreatefromstring((string) file_get_contents($file->getRealPath()));

        if ($image === false) {
            // Reached only if something slips past validation — HEIC/HEIF being
            // the realistic case, which GD cannot decode.
            throw new RuntimeException('Unsupported image format.');
        }

        try {
            $image = $this->applyOrientation($image, $file);
            $image = $this->downscale($image);

            imagealphablending($image, false);
            imagesavealpha($image, true);

            ob_start();
            imagewebp($image, null, self::QUALITY);
            $binary = (string) ob_get_clean();
        } finally {
            imagedestroy($image);
        }

        if ($binary === '') {
            throw new RuntimeException('Image could not be encoded.');
        }

        $path = self::DIRECTORY.'/'.$uuid.'.webp';

        // put() returns false on failure and the 'local' disk is configured with
        // 'throw' => false, so a full disk or a permissions problem produces no
        // exception at all. Unchecked, the caller stores a photo_path pointing
        // at a file that was never written.
        if (Storage::disk(self::DISK)->put($path, $binary) === false) {
            throw new RuntimeException(sprintf(
                'Write failed: disk "%s", path "%s", %d bytes. Free space: %s.',
                self::DISK,
                $path,
                strlen($binary),
                $this->freeSpace(),
            ));
        }

        return $path;
    }

    /** Human-readable free space on the disk's root, for failure diagnostics. */
    protected function freeSpace(): string
    {
        $bytes = @disk_free_space(storage_path('app/private'));

        return $bytes === false ? 'unknown' : round($bytes / 1048576).' MB';
    }

    public function delete(?string $path): void
    {
        if ($path && Storage::disk(self::DISK)->exists($path)) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    /**
     * EXIF only exists on JPEG/TIFF, and only when the extension is loaded.
     * Anything else is already upright.
     *
     * @param  \GdImage  $image
     * @return \GdImage
     */
    protected function applyOrientation($image, UploadedFile $file)
    {
        if (! function_exists('exif_read_data') || ! in_array($file->getMimeType(), ['image/jpeg', 'image/tiff'], true)) {
            return $image;
        }

        $exif = @exif_read_data($file->getRealPath());
        $orientation = (int) ($exif['Orientation'] ?? 1);

        // imagerotate() turns counter-clockwise, so the angles are negated.
        $rotate = match ($orientation) {
            3, 4 => 180,
            5, 6 => -90,
            7, 8 => 90,
            default => 0,
        };

        if ($rotate !== 0) {
            $rotated = imagerotate($image, $rotate, 0);

            if ($rotated !== false) {
                imagedestroy($image);
                $image = $rotated;
            }
        }

        // 2/5/7 are additionally mirrored.
        if (in_array($orientation, [2, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        } elseif ($orientation === 4) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        return $image;
    }

    /**
     * @param  \GdImage  $image
     * @return \GdImage
     */
    protected function downscale($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $longest = max($width, $height);

        if ($longest <= self::MAX_EDGE) {
            return $image;
        }

        $ratio = self::MAX_EDGE / $longest;
        $target = [(int) round($width * $ratio), (int) round($height * $ratio)];

        // imagecopyresampled rather than imagescale(): imagescale() with
        // IMG_BICUBIC returns false on some rotated truecolor images, and a
        // silent fallback there means full-size originals get stored.
        $scaled = imagecreatetruecolor($target[0], $target[1]);

        imagealphablending($scaled, false);
        imagesavealpha($scaled, true);
        imagefill($scaled, 0, 0, imagecolorallocatealpha($scaled, 0, 0, 0, 127));

        imagecopyresampled($scaled, $image, 0, 0, 0, 0, $target[0], $target[1], $width, $height);
        imagedestroy($image);

        return $scaled;
    }
}
