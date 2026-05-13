<?php

namespace App\Modules\AdminCompany\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * Single JPEG avatar per user at storage/app/public/super-user-avatars/{user_id}.jpg
 */
class SuperUserAvatarStorageService
{
    public const MAX_SIDE = 512;

    /**
     * @return string|null Absolute public URL when file exists
     */
    public static function publicUrl(int $userId): ?string
    {
        $rel = static::relativePath($userId);
        if ($rel === null) {
            return null;
        }

        return rtrim(config('app.url'), '/') . '/storage/' . $rel;
    }

    public static function relativePath(int $userId): ?string
    {
        $path = static::diskPath($userId);
        if (! Storage::disk('public')->exists($path)) {
            return null;
        }

        return $path;
    }

    public function store(int $userId, UploadedFile $file): void
    {
        $mime = $file->getMimeType() ?: '';
        $bytes = file_get_contents($file->getRealPath() ?: $file->getPathname());
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('Empty file upload.');
        }

        Storage::disk('public')->makeDirectory('super-user-avatars');

        if ($mime === 'image/svg+xml') {
            throw new RuntimeException('Use PNG or JPEG for profile photos.');
        }

        if (! function_exists('imagecreatefromstring')) {
            throw new RuntimeException('PHP GD extension is required to process avatar images.');
        }

        $src = @imagecreatefromstring($bytes);
        if (! $src) {
            throw new RuntimeException('Could not read image. Use PNG or JPEG.');
        }

        $out = static::resizeToMaxSide($src, static::MAX_SIDE);
        imagedestroy($src);

        ob_start();
        imagejpeg($out, null, 88);
        $jpeg = ob_get_clean();
        imagedestroy($out);

        Storage::disk('public')->put(static::diskPath($userId), $jpeg ?? '');
    }

    public function delete(int $userId): void
    {
        $path = static::diskPath($userId);
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private static function diskPath(int $userId): string
    {
        return 'super-user-avatars/' . $userId . '.jpg';
    }

    /** @param resource|\GdImage $img */
    private static function resizeToMaxSide($img, int $maxSide)
    {
        $w = imagesx($img);
        $h = imagesy($img);
        if ($w <= 0 || $h <= 0) {
            return $img;
        }

        $scale = min($maxSide / $w, $maxSide / $h, 1.0);
        $nw = max(1, (int) round($w * $scale));
        $nh = max(1, (int) round($h * $scale));
        $dst = imagecreatetruecolor($nw, $nh);
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $img, 0, 0, 0, 0, $nw, $nh, $w, $h);

        return $dst;
    }
}
