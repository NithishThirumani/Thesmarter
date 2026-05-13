<?php

namespace App\Modules\AdminProducts\Support;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Reads the canonical bulk template sheet `Products_Upload` (same as ProductBulkUploadTemplateBuilder).
 */
final class ProductBulkUploadExcelParser
{
    /** @return list<array<string, mixed>> */
    public function parseFile(string $absolutePath): array
    {
        if (! is_readable($absolutePath)) {
            throw new SpreadsheetException('Upload file could not be read.');
        }

        $reader = IOFactory::createReader('Xlsx');
        $reader->setReadDataOnly(false);
        $reader->setReadEmptyCells(true);

        $spreadsheet = $reader->load($absolutePath);

        try {
            $sheet = $this->resolveProductsWorksheet($spreadsheet);
            $headerRow = $this->detectHeaderRow($sheet);
            $firstDataRow = $headerRow + 1;
            // Same ~500 editable body rows as ERP template sheet (ENTRY_LAST_ROW = last template row vs header).
            $lastSoftCap = $headerRow + (ProductBulkUploadTemplateBuilder::ENTRY_LAST_ROW - 1);
            // Always scan through the full template band. Relying on getHighest* is fragile (WPS sparse cells,
            // odd dimensions). Cost is only ~500 lightweight row reads.
            $limitRow = $lastSoftCap;

            /** @var list<array<string, mixed>> $out */
            $out = [];

            for ($rowIndex = $firstDataRow; $rowIndex <= $limitRow; ++$rowIndex) {
                $extracted = $this->extractRow($sheet, $rowIndex);

                if ($this->rowIsSkipped($extracted)) {
                    continue;
                }

                $extracted['_sheet_row'] = $rowIndex;
                $extracted['_header_row'] = $headerRow;

                $out[] = $extracted;
            }

            return $out;
        } finally {
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }
    }

    private function resolveProductsWorksheet(Spreadsheet $spreadsheet): Worksheet
    {
        $target = ProductBulkUploadTemplateBuilder::SHEET_PRODUCTS;
        $named = $spreadsheet->getSheetByName($target);
        if ($named !== null) {
            return $named;
        }

        foreach ($spreadsheet->getWorksheetIterator() as $candidate) {
            if (strcasecmp(trim($candidate->getTitle()), $target) === 0) {
                return $candidate;
            }
        }

        foreach ($spreadsheet->getWorksheetIterator() as $candidate) {
            if ($this->sheetLooksLikeProductUploadGrid($candidate)) {
                return $candidate;
            }
        }

        return $spreadsheet->getSheet(0);
    }

    /** True when any of the first rows look like canonical template headings. */
    private function sheetLooksLikeProductUploadGrid(Worksheet $sheet): bool
    {
        for ($r = 1; $r <= 30; ++$r) {
            $a = $this->plainTextFromCell($sheet, 1, $r);
            if (! $this->headerLooksLikeProductNameColumn($a)) {
                continue;
            }
            if (strtolower(trim($a)) === 'name' && ! $this->rowLooksLikeCanonicalTemplateHeader($sheet, $r)) {
                continue;
            }
            // Instructions tab mentions "product_name" in prose; require the real cost/sell/branch header band.
            if (! $this->rowLooksLikeCanonicalTemplateHeader($sheet, $r)) {
                continue;
            }

            return true;
        }

        return false;
    }

    /** Header row detection for exports that prepend title rows above the canonical header. */
    private function detectHeaderRow(Worksheet $sheet): int
    {
        for ($r = 1; $r <= 30; ++$r) {
            $a = $this->plainTextFromCell($sheet, 1, $r);
            if (! $this->headerLooksLikeProductNameColumn($a)) {
                continue;
            }
            if (strtolower(trim($a)) === 'name' && ! $this->rowLooksLikeCanonicalTemplateHeader($sheet, $r)) {
                continue;
            }
            if (! $this->rowLooksLikeCanonicalTemplateHeader($sheet, $r)) {
                continue;
            }

            return $r;
        }

        for ($r = 1; $r <= 30; ++$r) {
            if ($this->rowLooksLikeCanonicalTemplateHeader($sheet, $r)) {
                return $r;
            }
        }

        return 1;
    }

    private function headerLooksLikeProductNameColumn(string $cell): bool
    {
        $s = strtolower(trim($cell));

        if ($s === '') {
            return false;
        }

        // Canonical template: "product_name *", "Product Name", or short "Name" in column A.
        return preg_match('/product[_\s-]*name|item[_\s-]*name|product\s*\*|\barticle\s*name|^name$/iu', $cell) === 1;
    }

    /**
     * True when this row has the expected price + branch column headings (guards false "Name" hits).
     */
    private function rowLooksLikeCanonicalTemplateHeader(Worksheet $sheet, int $r): bool
    {
        $g = strtolower($this->plainTextFromCell($sheet, 7, $r));
        $h = strtolower($this->plainTextFromCell($sheet, 8, $r));
        $n = strtolower($this->plainTextFromCell($sheet, 14, $r));

        return (str_contains($g, 'cost') && str_contains($h, 'sell')) || str_contains($n, 'branch');
    }

    /** @return array<string, mixed> */
    private function extractRow(Worksheet $sheet, int $rowIndex): array
    {
        /** Column index 1-based → key */
        return [
            'product_name' => $this->stringFromCell($sheet, 1, $rowIndex),
            'product_code' => $this->stringFromCell($sheet, 2, $rowIndex),
            'product_type' => $this->normalizeProductType($this->stringFromCell($sheet, 3, $rowIndex)),
            'product_brand' => $this->nullableStringCell($sheet, 4, $rowIndex),
            'product_hsn_code' => $this->nullableStringCell($sheet, 5, $rowIndex),
            'product_barcode' => $this->nullableStringCell($sheet, 6, $rowIndex),
            'cost_price' => $this->moneyFromCell($sheet, 7, $rowIndex),
            'sell_price' => $this->moneyFromCell($sheet, 8, $rowIndex),
            'tax_id_raw' => $this->scalarFromCell($sheet, 9, $rowIndex),
            'tax_inclusive_flag' => $this->normalizeYn($this->nullableStringCell($sheet, 11, $rowIndex)),
            'tags_raw' => $this->nullableStringCell($sheet, 12, $rowIndex),
            'opening_stock' => $this->quantityFromCell($sheet, 13, $rowIndex),
            'branch_id_raw' => $this->scalarFromCell($sheet, 14, $rowIndex),
            'stock_batch' => $this->nullableStringCell($sheet, 16, $rowIndex),
            'expiry_date_raw' => $this->expiryFromCell($sheet, 17, $rowIndex),
            'company_id_sheet' => $this->intOrNullFromCell($sheet, 19, $rowIndex),
            'catalogue_id_sheet' => $this->intOrNullFromCell($sheet, 20, $rowIndex),
        ];
    }

    private function rowIsSkipped(array $extracted): bool
    {
        return trim((string) ($extracted['product_name'] ?? '')) === '';
    }

    /**
     * Reads display text reliably (handles RichText, shared refs, formulae cached values).
     */
    private function plainTextFromCell(Worksheet $sheet, int $col1, int $row): string
    {
        $coordinate = Coordinate::stringFromColumnIndex($col1).$row;
        $cell = $sheet->getCell($coordinate);

        $raw = $cell->getValue();
        if ($raw instanceof RichText) {
            return trim((string) $raw->getPlainText());
        }
        if ($raw !== null && $raw !== '') {
            if (is_scalar($raw)) {
                return trim((string) $raw);
            }
        }

        $calc = $cell->getCalculatedValue();
        if ($calc instanceof RichText) {
            return trim((string) $calc->getPlainText());
        }
        if ($calc !== null && $calc !== '') {
            return trim((string) $calc);
        }

        return trim((string) $cell->getFormattedValue());
    }

    private function stringFromCell(Worksheet $sheet, int $col1, int $row): string
    {
        return $this->plainTextFromCell($sheet, $col1, $row);
    }

    private function nullableStringCell(Worksheet $sheet, int $col1, int $row): ?string
    {
        $v = $this->plainTextFromCell($sheet, $col1, $row);

        return $v === '' ? null : $v;
    }

    /** @return float|int|null */
    private function scalarFromCell(Worksheet $sheet, int $col1, int $row)
    {
        $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($col1).$row);
        $v = $cell->getCalculatedValue();
        if (($v === '' || $v === null) && method_exists($cell, 'getOldCalculatedValue')) {
            $v = $cell->getOldCalculatedValue();
        }
        if ($v === '' || $v === null) {
            $v = $cell->getValue();
        }
        if ($v === '' || $v === null) {
            return null;
        }
        if (is_numeric($v)) {
            return 0 + (float) $v;
        }
        $trim = trim((string) $v);

        return $trim === '' ? null : $trim;
    }

    /** @return float|null */
    private function moneyFromCell(Worksheet $sheet, int $col1, int $row)
    {
        $v = $this->scalarFromCell($sheet, $col1, $row);
        if ($v === null) {
            return null;
        }
        if (is_string($v)) {
            return (float) str_replace(',', '', $v);
        }

        return (float) $v;
    }

    /** @return float|null */
    private function quantityFromCell(Worksheet $sheet, int $col1, int $row)
    {
        return $this->moneyFromCell($sheet, $col1, $row);
    }

    private function intOrNullFromCell(Worksheet $sheet, int $col1, int $row): ?int
    {
        $v = $this->scalarFromCell($sheet, $col1, $row);
        if ($v === null || $v === '') {
            return null;
        }
        if (is_numeric($v)) {
            return (int) round((float) $v);
        }

        return null;
    }

    private function expiryFromCell(Worksheet $sheet, int $col1, int $row): ?string
    {
        $cell = $sheet->getCell(Coordinate::stringFromColumnIndex($col1).$row);
        $v = $cell->getValue();
        if ($v === null || $v === '') {
            return null;
        }
        try {
            if (ExcelDate::isDateTime($cell)) {
                return date('Y-m-d H:i:s', ExcelDate::excelToTimestamp((float) $v));
            }
        } catch (\Throwable $e) {
            report($e);
        }
        $s = trim((string) $cell->getFormattedValue());
        if ($s === '') {
            return null;
        }

        return $s;
    }

    private function normalizeProductType(?string $t): ?string
    {
        if ($t === null || strtoupper(trim($t)) === '') {
            return null;
        }
        $u = strtoupper(substr(trim($t), 0, 1));

        return in_array($u, ['C', 'N'], true) ? $u : null;
    }

    /** @return 'Y'|'N'|null */
    private function normalizeYn(?string $v): ?string
    {
        if ($v === null || $v === '') {
            return null;
        }
        $u = strtoupper(substr(trim($v), 0, 1));

        return in_array($u, ['Y', 'N'], true) ? $u : null;
    }
}
