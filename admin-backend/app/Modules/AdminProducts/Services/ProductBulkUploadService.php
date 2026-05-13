<?php

namespace App\Modules\AdminProducts\Services;

use App\BranchDetail;
use App\MerchantCatalogue;
use App\Modules\AdminProducts\Support\ProductBulkUploadExcelParser;
use App\Modules\AdminProducts\Support\ProductBulkUploadErrorFormatter;
use App\Modules\AdminProducts\Validation\ProductBulkUploadValidator;
use App\TaxMaster;
use App\UniversalTags;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Parses, validates (strict), optionally persists merchant bulk product rows — one DB transaction per product row.
 */
final class ProductBulkUploadService
{
    /** Rows above this enqueue background job (when PRODUCTS_BULK_USE_QUEUE=true). */
    public const LARGE_IMPORT_ROW_THRESHOLD = 500;

    private $parser;

    public function __construct(?ProductBulkUploadExcelParser $parser = null)
    {
        $this->parser = $parser ?? new ProductBulkUploadExcelParser;
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(
        string $absolutePath,
        int $companyId,
        int $catalogueId,
        int $actingUserId,
        bool $dryRun,
        bool $forceSyncLargeImport = false
    ): array {
        $catalogueRow = MerchantCatalogue::query()
            ->whereKey($catalogueId)
            ->first();
        if (! $catalogueRow || (int) $catalogueRow->company_id !== $companyId) {
            return [
                'success' => false,
                'status' => 'FAILED',
                'message' => 'Catalogue not found for this company.',
                'total_rows' => 0,
                'valid_rows' => 0,
                'error_rows' => 0,
                'inserted' => 0,
                'failed' => 0,
                'errors' => [],
                'error_report_csv' => '',
            ];
        }

        $parserRows = $this->parser->parseFile($absolutePath);
        $totalRows = count($parserRows);

        if (
            ! $forceSyncLargeImport
            && ! $dryRun
            && filter_var((string) env('PRODUCTS_BULK_USE_QUEUE', ''), FILTER_VALIDATE_BOOLEAN)
            && $totalRows > self::LARGE_IMPORT_ROW_THRESHOLD
        ) {
            return [
                'success' => true,
                'defer' => true,
                'status' => 'QUEUED',
                'total_rows' => $totalRows,
                'dry_run' => false,
                'message' => 'Large import delegated to queue (see PRODUCTS_BULK_USE_QUEUE).',
            ];
        }

        return $this->executeWithParsedRows(
            $parserRows,
            $companyId,
            $catalogueId,
            $actingUserId,
            $dryRun
        );
    }

    /** @return list<int> */
    private function activeTaxIds(int $companyId): array
    {
        $q = TaxMaster::query()->where('company_id', $companyId);
        if (Schema::hasColumn('tax_master', 'tax_status')) {
            $q->where('tax_status', 'A');
        }

        return $q->orderBy('tax_id')->pluck('tax_id')->map(static function ($id) {
            return (int) $id;
        })->values()->all();
    }

    /** @return list<int> */
    private function activeBranchIds(int $companyId): array
    {
        return BranchDetail::query()
            ->where('company_id', $companyId)
            ->where(function ($inner) {
                $inner
                    ->whereIn('branch_status', [1, '1'])
                    ->orWhereIn('branch_status', ['A', 'a']);
            })
            ->orderBy('branch_id')
            ->pluck('branch_id')
            ->map(static function ($id) {
                return (int) $id;
            })
            ->values()
            ->all();
    }

    /** @return list<string> */
    private function existingProductCodesForCompany(int $companyId): array
    {
        return DB::table('merchant_catalogue_products as p')
            ->join('merchant_catalogue as c', 'p.catalogue_id', '=', 'c.catalogue_id')
            ->where('c.company_id', $companyId)
            ->whereRaw("TRIM(p.product_code) <> ''")
            ->pluck('p.product_code')
            ->map(static function ($c) {
                return (string) $c;
            })
            ->all();
    }

    /** @return list<string> */
    private function existingBarcodesForCompany(int $companyId): array
    {
        return DB::table('merchant_catalogue_products as p')
            ->join('merchant_catalogue as c', 'p.catalogue_id', '=', 'c.catalogue_id')
            ->where('c.company_id', $companyId)
            ->whereRaw("TRIM(p.product_barcode) <> ''")
            ->pluck('p.product_barcode')
            ->map(static function ($c) {
                return (string) $c;
            })
            ->all();
    }

    /** @return array<string, int> lowercase tag → id */
    private function preloadTagMapLower(int $companyId): array
    {
        $map = [];
        $rows = UniversalTags::query()
            ->where('company_id', $companyId)
            ->get(['tag_id', 'tag_name']);

        foreach ($rows as $r) {
            $k = mb_strtolower(trim((string) $r->tag_name), 'UTF-8');
            $map[$k] = (int) $r->tag_id;
        }

        return $map;
    }

    /**
     * @param  list<array<string, mixed>>  $parserRows
     * @return array<string, mixed>
     */
    private function executeWithParsedRows(array $parserRows, int $companyId, int $catalogueId, int $actingUserId, bool $dryRun): array
    {
        $totalRows = count($parserRows);

        $taxIds = $this->activeTaxIds($companyId);
        $branchIds = $this->activeBranchIds($companyId);
        $existingCodes = $this->existingProductCodesForCompany($companyId);
        $existingBarcodes = $this->existingBarcodesForCompany($companyId);

        $validator = new ProductBulkUploadValidator(
            $companyId,
            $catalogueId,
            $taxIds,
            $branchIds,
            $existingCodes,
            $existingBarcodes
        );

        $validated = $validator->validateAll($parserRows);
        /** @var list<array<string, mixed>> $valid */
        $valid = $validated['valid'];
        /** @var list<array{row: int, message: string}> $validationErrors */
        $validationErrors = $validated['errors'];

        $validCount = count($valid);
        $validationErrorRows = count($validationErrors);

        $basePayload = [
            'success' => true,
            'defer' => false,
            'dry_run' => $dryRun,
            'total_rows' => $totalRows,
            'valid_rows' => $validCount,
            'error_rows' => $validationErrorRows,
            'errors' => $validationErrors,
            'error_report_csv' => ProductBulkUploadErrorFormatter::toCsv($validationErrors),
        ];

        if ($dryRun) {
            return array_merge($basePayload, [
                'status' => 'COMPLETED',
                'inserted' => 0,
                'failed' => 0,
                'persist_errors' => [],
                'hint' => $this->bulkUploadHint(true, $totalRows, $validCount, 0, 0),
            ]);
        }

        $tagMap = $this->preloadTagMapLower($companyId);

        /** @var list<array{row: int, message: string}> $persistErrors */
        $persistErrors = [];
        $inserted = 0;
        $persister = new ProductBulkUploadPersistService($companyId, $catalogueId, $actingUserId);

        foreach ($valid as $row) {
            try {
                $persister->persistRow($row, $tagMap);
                ++$inserted;
            } catch (Throwable $e) {
                report($e);
                $persistErrors[] = [
                    'row' => (int) ($row['sheet_row'] ?? 0),
                    'message' => 'Persist failed: '.$e->getMessage(),
                ];
            }
        }

        $allErrors = array_merge($validationErrors, $persistErrors);

        return [
            'success' => true,
            'defer' => false,
            'status' => 'COMPLETED',
            'dry_run' => false,
            'total_rows' => $totalRows,
            'valid_rows' => $validCount,
            'error_rows' => $validationErrorRows,
            'inserted' => $inserted,
            'failed' => count($persistErrors),
            'errors' => $allErrors,
            'validation_errors' => $validationErrors,
            'persist_errors' => $persistErrors,
            'error_report_csv' => ProductBulkUploadErrorFormatter::toCsv($allErrors),
            'hint' => $this->bulkUploadHint(false, $totalRows, $validCount, $inserted, count($persistErrors)),
        ];
    }

    /**
     * Human-readable diagnostics when callers see HTTP 200 with zero inserts.
     */
    private function bulkUploadHint(bool $dryRun, int $totalRows, int $validCount, int $inserted, int $persistFailCount): ?string
    {
        if ($totalRows === 0) {
            return 'No product rows were read. Prefer the exported template sheet "Products_Upload" so columns A–T align; '
                .'if you use another sheet tab, column A must be the item name heading and cost/sell/branch headings must align with columns G,H,N.';
        }
        if ($dryRun && $validCount === 0) {
            return 'Every parsed row failed validation — open errors or download CSV (branch/tax/catalogue mismatches and duplicate SKU/barcode codes are common).';
        }
        if (! $dryRun && $validCount === 0) {
            return 'Nothing was imported because no rows passed validation.';
        }
        if (! $dryRun && $inserted === 0 && $persistFailCount > 0) {
            return 'Validation passed but every insert failed inside the DB transaction — see persist_errors.';
        }

        return null;
    }
}
