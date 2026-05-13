<?php

namespace App\Modules\AdminProducts\Validation;

use App\Modules\AdminProducts\Support\ProductBulkUploadRowNormalizer;

/**
 * Strict row validation with preloaded tax/branch maps and company-scoped uniqueness.
 */
final class ProductBulkUploadValidator
{
    /** @var array<int, true> */
    private $taxIdSet;

    /** @var array<int, true> */
    private $branchIdSet;

    /** @var array<string, true> */
    private $existingProductCodesLower;

    /** @var array<string, true> */
    private $existingBarcodesLower;

    private $companyId;

    private $catalogueId;

    /**
     * @param  array<int>  $taxIds
     * @param  array<int>  $branchIds
     * @param  array<string>  $existingProductCodes
     * @param  array<string>  $existingBarcodes
     */
    public function __construct(
        int $companyId,
        int $catalogueId,
        array $taxIds,
        array $branchIds,
        array $existingProductCodes,
        array $existingBarcodes
    ) {
        $this->companyId = $companyId;
        $this->catalogueId = $catalogueId;
        $this->taxIdSet = array_fill_keys($taxIds, true);
        $this->branchIdSet = array_fill_keys($branchIds, true);
        $this->existingProductCodesLower = [];
        foreach ($existingProductCodes as $c) {
            $k = self::normCodeKey((string) $c);
            if ($k !== '') {
                $this->existingProductCodesLower[$k] = true;
            }
        }
        $this->existingBarcodesLower = [];
        foreach ($existingBarcodes as $b) {
            $k = self::normCodeKey((string) $b);
            if ($k !== '') {
                $this->existingBarcodesLower[$k] = true;
            }
        }
    }

    /**
     * @param  list<array<string, mixed>>  $parserRows
     * @return array{valid: list<array<string, mixed>>, errors: list<array{row: int, message: string}>}
     */
    public function validateAll(array $parserRows): array
    {
        $batchCodesSeen = [];
        $batchBarcodeSeen = [];
        $errors = [];
        $valid = [];

        foreach ($parserRows as $raw) {
            $sheetRow = (int) ($raw['_sheet_row'] ?? 0);
            $normalized = ProductBulkUploadRowNormalizer::forValidation($raw, $this->companyId, $this->catalogueId);

            $rowErrors = $this->validateOne(
                $normalized,
                $batchCodesSeen,
                $batchBarcodeSeen
            );

            if ($rowErrors !== []) {
                foreach ($rowErrors as $msg) {
                    $errors[] = ['row' => $sheetRow, 'message' => $msg];
                }
                continue;
            }

            $valid[] = $this->toPersistRow($normalized);
        }

        return ['valid' => $valid, 'errors' => $errors];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, true>  $batchCodesSeen
     * @param  array<string, true>  $batchBarcodeSeen
     * @return list<string>
     */
    private function validateOne(array $row, array &$batchCodesSeen, array &$batchBarcodeSeen): array
    {
        $e = [];

        $name = trim((string) ($row['product_name'] ?? ''));
        if ($name === '') {
            $e[] = 'product_name is required.';
        }

        $codeKey = self::normCodeKey((string) ($row['resolved_product_code'] ?? ''));
        if ($codeKey === '') {
            $e[] = 'product_code (resolved) is empty.';
        } else {
            if (isset($this->existingProductCodesLower[$codeKey])) {
                $e[] = 'product_code already exists for this company.';
            }
            if (isset($batchCodesSeen[$codeKey])) {
                $e[] = 'Duplicate product_code in upload file.';
            }
        }

        $barcode = $row['product_barcode'] ?? null;
        if ($barcode !== null && trim((string) $barcode) !== '') {
            $bKey = self::normCodeKey((string) $barcode);
            if (isset($this->existingBarcodesLower[$bKey])) {
                $e[] = 'product_barcode already exists for this company.';
            }
            if (isset($batchBarcodeSeen[$bKey])) {
                $e[] = 'Duplicate product_barcode in upload file.';
            }
        }

        $taxId = $row['tax_id'] ?? null;
        if ($taxId !== null) {
            if (! isset($this->taxIdSet[$taxId])) {
                $e[] = 'Invalid tax_id for this company.';
            }
        }

        $incl = $row['tax_inclusive_flag'] ?? null;
        if ($incl === 'Y' && ($taxId === null)) {
            $e[] = 'tax_inclusive_flag Y requires tax_id.';
        }

        $branchId = $row['branch_id'] ?? null;
        if ($branchId === null) {
            $e[] = 'branch_id is required.';
        } elseif (! isset($this->branchIdSet[$branchId])) {
            $e[] = 'Invalid branch_id for this company.';
        }

        $cost = $row['cost_price'];
        $sell = $row['sell_price'];
        if ($cost === null || ! is_numeric($cost)) {
            $e[] = 'cost_price is required and must be numeric.';
        } elseif ((float) $cost < 0) {
            $e[] = 'cost_price must be >= 0.';
        }
        if ($sell === null || ! is_numeric($sell)) {
            $e[] = 'sell_price is required and must be numeric.';
        } elseif ((float) $sell < 0) {
            $e[] = 'sell_price must be >= 0.';
        }

        $open = $row['opening_stock'];
        if ($open === null || $open === '') {
            $open = 0.0;
        } elseif (! is_numeric($open)) {
            $e[] = 'opening_stock must be numeric.';
        } elseif ((float) $open < 0) {
            $e[] = 'opening_stock must be >= 0.';
        }

        $ptype = $row['product_type'] ?? null;
        if ($ptype === null || $ptype === '') {
            $ptype = 'N';
        }
        if (! in_array($ptype, ['C', 'N'], true)) {
            $e[] = 'product_type must be C or N.';
        }

        if ($e !== []) {
            return $e;
        }

        if ($codeKey !== '') {
            $batchCodesSeen[$codeKey] = true;
        }
        if ($barcode !== null && trim((string) $barcode) !== '') {
            $batchBarcodeSeen[self::normCodeKey((string) $barcode)] = true;
        }

        return [];
    }

    /**
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    private function toPersistRow(array $row): array
    {
        $open = $row['opening_stock'];
        if ($open === null || $open === '') {
            $open = 0.0;
        }

        $ptypeEffective = ($row['product_type'] ?? null);
        $ptypeEffective = ($ptypeEffective === null || $ptypeEffective === '') ? 'N' : (string) $ptypeEffective;

        return [
            'sheet_row' => (int) $row['_sheet_row'],
            'product_name' => (string) $row['product_name'],
            'product_code' => (string) $row['resolved_product_code'],
            'product_type' => $ptypeEffective,
            'product_brand' => $row['product_brand'],
            'product_hsn_code' => $row['product_hsn_code'],
            'product_barcode' => $row['product_barcode'] !== null && trim((string) $row['product_barcode']) === ''
                ? null
                : $row['product_barcode'],
            'cost_price' => (float) $row['cost_price'],
            'sell_price' => (float) $row['sell_price'],
            'tax_id' => $row['tax_id'],
            'tax_inclusive_flag' => $row['tax_inclusive_flag'],
            'tags' => $row['tags'] ?? [],
            'opening_stock' => (float) $open,
            'branch_id' => (int) $row['branch_id'],
            'stock_batch' => $row['stock_batch'],
            'expiry_date_raw' => $row['expiry_date_raw'],
        ];
    }

    private static function normCodeKey(string $s): string
    {
        return strtolower(trim($s));
    }
}
