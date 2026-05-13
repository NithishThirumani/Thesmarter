<?php

namespace App\Modules\AdminProducts\Support;

/**
 * Normalizes a raw parser row + validation context into a persist-ready row DTO (plain array).
 */
final class ProductBulkUploadRowNormalizer
{
    /**
     * @param  array<string, mixed>  $raw  From ProductBulkUploadExcelParser (includes _sheet_row, _header_row)
     * @return array<string, mixed>
     */
    public static function forValidation(array $raw, int $companyId, int $catalogueId): array
    {
        $tags = self::splitTags($raw['tags_raw'] ?? null);
        $sheetRow = (int) ($raw['_sheet_row'] ?? 0);
        $headerRow = max(1, (int) ($raw['_header_row'] ?? 1));
        $skuSeq = max(1, $sheetRow - $headerRow);
        $optionalCode = trim((string) ($raw['product_code'] ?? ''));
        $resolved = $optionalCode !== ''
            ? $optionalCode
            : $companyId.'-'.$catalogueId.'-SKU-'.sprintf('%06d', $skuSeq);

        return [
            '_sheet_row' => $sheetRow,
            'product_name' => trim((string) ($raw['product_name'] ?? '')),
            'resolved_product_code' => $resolved,
            'product_type' => $raw['product_type'],
            'product_brand' => $raw['product_brand'] ?? null,
            'product_hsn_code' => $raw['product_hsn_code'] ?? null,
            'product_barcode' => $raw['product_barcode'] ?? null,
            'cost_price' => $raw['cost_price'],
            'sell_price' => $raw['sell_price'],
            'tax_id' => self::coerceIntOrNull($raw['tax_id_raw'] ?? null),
            'tax_inclusive_flag' => $raw['tax_inclusive_flag'] ?? null,
            'tags' => $tags,
            'opening_stock' => $raw['opening_stock'],
            'branch_id' => self::coerceIntOrNull($raw['branch_id_raw'] ?? null),
            'stock_batch' => $raw['stock_batch'] ?? null,
            'expiry_date_raw' => $raw['expiry_date_raw'] ?? null,
        ];
    }

    /** @return list<string> */
    public static function splitTags(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }
        $parts = preg_split('/[,;|]+/u', $raw) ?: [];
        $out = [];
        foreach ($parts as $p) {
            $t = trim((string) $p);
            if ($t !== '') {
                $out[] = $t;
            }
        }

        return $out;
    }

    /**
     * @param  mixed  $v
     */
    private static function coerceIntOrNull($v): ?int
    {
        if ($v === null || $v === '') {
            return null;
        }
        if (is_string($v) && trim($v) === '') {
            return null;
        }
        if (is_numeric($v)) {
            return (int) round((float) $v);
        }

        return null;
    }
}
