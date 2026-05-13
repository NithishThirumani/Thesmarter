<?php

namespace App\Modules\AdminCompany\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Stores company logos on disk under public/company-logos/{uuid}/ with multiple sizes.
 * company_logo column holds only the UUID key.
 */
class CompanyLogoStorageService
{
    public const SIZES = [64, 128, 256];

    /**
     * Process upload: raster (JPEG/PNG) → original + thumbnails; SVG → copied per size.
     *
     * @return string UUID folder key
     */
    public function store(UploadedFile $file): string
    {
        $key = (string) Str::uuid();
        $dir = 'company-logos/' . $key;
        Storage::disk('public')->makeDirectory($dir);

        $mime = $file->getMimeType() ?: '';
        $bytes = file_get_contents($file->getRealPath() ?: $file->getPathname());
        if ($bytes === false || $bytes === '') {
            throw new RuntimeException('Empty file upload.');
        }

        $isSvg = $mime === 'image/svg+xml'
            || Str::endsWith(strtolower($file->getClientOriginalName()), '.svg');

        if ($isSvg) {
            Storage::disk('public')->put($dir . '/original.svg', $bytes);
            foreach (self::SIZES as $size) {
                Storage::disk('public')->put($dir . '/' . $size . '.svg', $bytes);
            }
            Storage::disk('public')->put($dir . '/.format', 'svg');

            return $key;
        }

        if (!function_exists('imagecreatefromstring')) {
            throw new RuntimeException('PHP GD extension is required to process PNG/JPEG logos.');
        }

        $src = @imagecreatefromstring($bytes);
        if (!$src) {
            throw new RuntimeException('Could not read image. Use PNG, JPEG, or SVG.');
        }

        Storage::disk('public')->put($dir . '/.format', 'raster');

        $orig = $this->resizeToMaxSide($src, 512);
        $this->saveJpegToDisk($orig, $dir . '/original.jpg');

        foreach (self::SIZES as $size) {
            $thumb = $this->resizeToMaxSide($src, $size);
            $this->saveJpegToDisk($thumb, $dir . '/' . $size . '.jpg');
        }

        imagedestroy($src);

        return $key;
    }

    /**
     * @param resource $img
     */
    private function resizeToMaxSide($img, int $maxSide)
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

    /**
     * @param resource $img
     */
    private function saveJpegToDisk($img, string $relativePath): void
    {
        ob_start();
        imagejpeg($img, null, 86);
        $data = ob_get_clean();
        Storage::disk('public')->put($relativePath, $data);
        imagedestroy($img);
    }

    public function deleteByKey(?string $key): void
    {
        if (!$key || !is_string($key)) {
            return;
        }
        $path = 'company-logos/' . $key;
        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->deleteDirectory($path);
        }
    }

    /**
     * Public URLs for API consumers.
     * Uses API streaming endpoint so storage:link is not required.
     *
     * Paths are root-relative so the browser uses the same origin as the admin UI (e.g. Vite :5173
     * with /api proxied). Avoids broken images when APP_URL points at a different host than the SPA.
     *
     * @return array{sm:string,md:string,lg:string,original:string}|null
     */
    public static function publicUrls(?string $key): ?array
    {
        if (!$key || !is_string($key)) {
            return null;
        }

        $base = '/api/public/company-logo/' . rawurlencode($key);

        return [
            'sm' => $base . '/sm',
            'md' => $base . '/md',
            'lg' => $base . '/lg',
            'original' => $base . '/original',
        ];
    }
}
