<?php

namespace App\Modules\AdminProducts\Support;

use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Builds the company-scoped bulk product upload workbook (PhpSpreadsheet).
 * Reference sheets Valid_Taxes / Valid_Branches are sheet-protected & read-oriented.
 */
final class ProductBulkUploadTemplateBuilder
{
    /** @see spec: dropdown source rows A2:A1000 */
    public const REF_LIST_LAST_ROW = 1000;

    /** Empty data rows below header (spec: 500–1000); inclusive of row 2 through this row */
    public const ENTRY_LAST_ROW = 501;

    public const SHEET_PRODUCTS = 'Products_Upload';

    public const SHEET_TAXES = 'Valid_Taxes';

    public const SHEET_BRANCHES = 'Valid_Branches';

    public const SHEET_INSTRUCTIONS = 'Instructions';

    /** Mandatory headers (yellow): product_name, cost_price, sell_price, branch_id */
    private const REQUIRED_COL_IDX = [0, 6, 7, 13];

    /** @param array<int, array{tax_id:int|string, tax_name:string}> $taxRows */
    /** @param array<int, array{branch_id:int|string, branch_name:string}> $branchRows */
    public function build(
        array $taxRows,
        array $branchRows,
        int $companyId,
        int $catalogueId
    ): Spreadsheet {
        $spreadsheet = new Spreadsheet;
        $products = $spreadsheet->getActiveSheet();
        $products->setTitle(self::SHEET_PRODUCTS);

        $taxSheet = new Worksheet($spreadsheet, self::SHEET_TAXES);
        $spreadsheet->addSheet($taxSheet);

        $branchSheet = new Worksheet($spreadsheet, self::SHEET_BRANCHES);
        $spreadsheet->addSheet($branchSheet);

        $instructions = new Worksheet($spreadsheet, self::SHEET_INSTRUCTIONS);
        $spreadsheet->addSheet($instructions);

        $this->populateReferenceSheet($taxSheet, ['tax_id', 'tax_name'], $taxRows);
        $this->populateReferenceSheet($branchSheet, ['branch_id', 'branch_name'], $branchRows);

        $this->protectReferenceSheet($taxSheet);
        $this->protectReferenceSheet($branchSheet);

        $this->buildProductsSheet($products, $companyId, $catalogueId);

        $this->buildInstructionsSheet($instructions);

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /** @param list<string> $headers */
    /** @param array<int, array<string, mixed>> $rows keyed rows as associative arrays aligned to headers order */
    private function populateReferenceSheet(Worksheet $sheet, array $headers, array $rows): void
    {
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue("{$col}1", $h);
            ++$col;
        }
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);

        $r = 2;
        foreach ($rows as $row) {
            if (isset($headers[0], $headers[1])) {
                $k0 = $headers[0];
                $k1 = $headers[1];
                $sheet->setCellValue('A'.$r, $row[$k0] ?? '');
                $sheet->setCellValue('B'.$r, $row[$k1] ?? '');
            }
            ++$r;
        }

        foreach (range('A', 'B') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }
    }

    private function protectReferenceSheet(Worksheet $sheet): void
    {
        $protection = $sheet->getProtection();
        $protection->setSheet(true);
        $protection->setPassword(bin2hex(random_bytes(16)));
    }

    private function buildProductsSheet(Worksheet $sheet, int $companyId, int $catalogueId): void
    {
        $headers = [
            'product_name *',
            'product_code (optional)',
            'product_type (C/N)',
            'product_brand',
            'product_hsn_code',
            'product_barcode',
            'cost_price *',
            'sell_price *',
            'tax_id (optional — blank if not taxable)',
            'tax_name (auto-filled / reference)',
            'tax_inclusive_flag (Y/N)',
            'tags (comma separated)',
            'opening_stock',
            'branch_id *',
            'branch_name (auto-filled / reference)',
            'stock_batch',
            'expiry_date',
            'resolved_product_code (formula — use when B is blank)',
            'company_id (hidden)',
            'catalogue_id (hidden)',
        ];

        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue("{$col}1", $h);
            ++$col;
        }

        $headerStyle = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
        ];
        $sheet->getStyle('A1:T1')->applyFromArray($headerStyle);

        $fillRequired = ['fill' => [
            'fillType' => Fill::FILL_SOLID,
            'startColor' => ['rgb' => 'FFE59C'],
        ]];
        foreach (self::REQUIRED_COL_IDX as $idx) {
            $sheet->getStyleByColumnAndRow($idx + 1, 1)->applyFromArray($fillRequired);
        }

        $taxList = '=\''.self::SHEET_TAXES."'!\$A\$2:\$A\$".self::REF_LIST_LAST_ROW;
        $branchList = '=\''.self::SHEET_BRANCHES."'!\$A\$2:\$A\$".self::REF_LIST_LAST_ROW;

        $maxRow = self::ENTRY_LAST_ROW;
        for ($row = 2; $row <= $maxRow; ++$row) {
            $sheet->setCellValueExplicit(
                'S'.$row,
                $companyId,
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
            );
            $sheet->setCellValueExplicit(
                'T'.$row,
                $catalogueId,
                \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_NUMERIC
            );

            // resolved_product_code (R): blank B → {company_id}-{catalogue_id}-SKU-000001 (uses hidden S,T)
            $sheet->setCellValue(
                'R'.$row,
                '=IF(TRIM(B'.$row.')="",$S'.$row.'&"-"&T'.$row.'&"-SKU-"&TEXT(ROW()-1,"000000"),TRIM(B'.$row.'))'
            );

            // tax_name (J), branch_name (O) — blanks + IFERROR for non-tax rows / lookup misses
            $sheet->setCellValue(
                'J'.$row,
                '=IF(TRIM(I'.$row.')="","",IFERROR(VLOOKUP(I'.$row.','.self::SHEET_TAXES.'!$A:$B,2,FALSE),""))'
            );
            $sheet->setCellValue(
                'O'.$row,
                '=IF(TRIM(N'.$row.')="","",IFERROR(VLOOKUP(N'.$row.','.self::SHEET_BRANCHES.'!$A:$B,2,FALSE),""))'
            );
        }

        $this->applyColumnListValidation($sheet, 'I', 2, $maxRow, $taxList, 'Optional: leave blank if not taxable, or choose tax_id from Valid_Taxes.');
        $this->applyColumnListValidation($sheet, 'N', 2, $maxRow, $branchList, 'Pick a branch_id from Valid_Branches.');

        $dvFlag = new DataValidation;
        $dvFlag->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setShowDropDown(false)
            ->setFormula1('"Y,N"')
            ->setShowErrorMessage(true)
            ->setErrorTitle('Invalid value')
            ->setError('Use Y or N only.');
        for ($row = 2; $row <= $maxRow; ++$row) {
            $sheet->getCell('K'.$row)->setDataValidation(clone $dvFlag);
        }

        $dvType = new DataValidation;
        $dvType->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setShowDropDown(false)
            ->setFormula1('"C,N"')
            ->setShowErrorMessage(true)
            ->setErrorTitle('Invalid value')
            ->setError('Use C or N only.');
        for ($row = 2; $row <= $maxRow; ++$row) {
            $sheet->getCell('C'.$row)->setDataValidation(clone $dvType);
        }

        $sheet->freezePane('A2');

        // Metadata columns (must stay to the right of resolved_product_code for formula $S refs)
        $sheet->getColumnDimension('S')->setVisible(false);
        $sheet->getColumnDimension('T')->setVisible(false);

        foreach (range('A', 'T') as $c) {
            $sheet->getColumnDimension($c)->setAutoSize(true);
        }
    }

    private function applyColumnListValidation(
        Worksheet $sheet,
        string $col,
        int $start,
        int $end,
        string $formula1,
        string $error
    ): void {
        $dv = new DataValidation;
        $dv->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setShowDropDown(false)
            ->setFormula1($formula1)
            ->setShowErrorMessage(true)
            ->setErrorTitle('Invalid value')
            ->setError($error);

        for ($row = $start; $row <= $end; ++$row) {
            $sheet->getCell($col.$row)->setDataValidation(clone $dv);
        }
    }

    private function buildInstructionsSheet(Worksheet $sheet): void
    {
        $lines = [
            'Bulk product upload — instructions',
            '',
            'Mandatory columns (marked *): product_name, cost_price, sell_price, branch_id.',
            '- product_code (column B): optional. If empty, resolved_product_code (column R) auto-fills using hidden company_id (S) and catalogue_id (T).',
            '- tax_id (column I): optional — leave blank for non-taxable products. tax_name stays empty when tax_id is blank.',
            '- product_type must be C or N. tax_inclusive_flag Y or N (optional when not taxable — still use dropdown where applicable).',
            '',
            'Example taxable row:',
            'Widget ACME | (optional SKU) | C | BrandX | 340119 | … | cost | sell | [tax id or blank] | (formula) | N | tags | qty | branch | …',
            '',
            'Important:',
            '- Companies may have no taxes in ERP: Valid_Taxes can be header-only — leave tax_id blank for exempt lines.',
            '- Use dropdowns where lists exist.',
            '- Do NOT edit Valid_Taxes or Valid_Branches sheets.',
            '- resolved_product_code, tax_name and branch_name are formulas — prefer editing B or I,N only.',
            '',
            'References:',
            '- tax_id: Valid_Taxes column A.',
            '- branch_id: Valid_Branches column A.',
        ];

        $r = 1;
        foreach ($lines as $line) {
            $sheet->setCellValue('A'.$r, $line);
            ++$r;
        }
        $sheet->getColumnDimension('A')->setWidth(96);
        $sheet->getStyle('A:A')->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    }
}
