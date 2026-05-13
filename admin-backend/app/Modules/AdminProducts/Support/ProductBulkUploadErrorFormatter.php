<?php

namespace App\Modules\AdminProducts\Support;

/**
 * CSV error attachments for downloadable client reports (UTF-8).
 */
final class ProductBulkUploadErrorFormatter
{
    /**
     * @param  list<array{row: int, message: string}>  $errors
     */
    public static function toCsv(array $errors): string
    {
        $fp = fopen('php://temp', 'r+');
        if ($fp === false) {
            return '';
        }
        fwrite($fp, "\xEF\xBB\xBF");
        fputcsv($fp, ['row', 'message']);
        foreach ($errors as $e) {
            fputcsv($fp, [$e['row'], $e['message']]);
        }
        rewind($fp);
        $csv = stream_get_contents($fp) ?: '';
        fclose($fp);

        return $csv;
    }
}
