<?php

namespace App\Modules\AdminProducts\Services;

use App\MerchantCatalogueProducts;
use App\MerchantProductPrices;
use App\MerchantProductTaxes;
use App\MerchantProductTaxInclusive;
use App\UniversalTags;
use App\UniversalTagsMapping;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Persists one validated bulk row (transaction per row; called inside service loop).
 */
final class ProductBulkUploadPersistService
{
    private $companyId;

    private $catalogueId;

    /** @var int ERP-style user id for stock_transaction.procured_by and universal_tags.created_by */
    private $actingUserId;

    public function __construct(int $companyId, int $catalogueId, int $actingUserId)
    {
        $this->companyId = $companyId;
        $this->catalogueId = $catalogueId;
        $this->actingUserId = $actingUserId;
    }

    /**
     * @param  array<string, mixed>  $row  Persist DTO from ProductBulkUploadValidator
     * @param  array<string, int>  $tagMapLowerNameToId  Mutable map (lowercase tag name → tag_id)
     *
     * @throws Throwable
     */
    public function persistRow(array $row, array &$tagMapLowerNameToId): void
    {
        $opening = (float) ($row['opening_stock'] ?? 0);
        $branchId = (int) $row['branch_id'];
        $expiry = $this->parseExpiry($row['expiry_date_raw'] ?? null);

        DB::transaction(function () use ($row, $opening, $branchId, $expiry, &$tagMapLowerNameToId): void {
            $this->persistWithinTx($row, $opening, $branchId, $expiry, $tagMapLowerNameToId);
        });
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array<string, int>  $tagMapLowerNameToId
     */
    private function persistWithinTx(array $row, float $opening, int $branchId, ?Carbon $expiry, array &$tagMapLowerNameToId): void
    {
        $product = new MerchantCatalogueProducts;
        $product->catalogue_id = $this->catalogueId;
        $product->product_type = $row['product_type'] ?? 'N';
        $product->product_logo = null;
        $product->product_name = $row['product_name'];
        $product->product_brand = $row['product_brand'];
        $product->product_code = $row['product_code'];
        $product->product_hsn_code = $row['product_hsn_code'];
        $product->product_barcode = $row['product_barcode'];
        $product->product_qr_code = null;
        $product->quantity_based_price_flag = 'N';
        $product->product_service_charge_flag = 'Y';
        $product->product_discount_flag = 'Y';
        $product->product_count_stock = $opening > 0 ? 'Y' : 'N';
        $product->measuring_unit_id = null;
        $product->product_status = 'A';
        $product->quantity_in_hand = $opening;
        $product->save();
        $productId = (int) $product->product_id;

        MerchantProductPrices::create([
            'product_id' => $productId,
            'product_cost_price' => (float) $row['cost_price'],
            'product_sell_price' => (float) $row['sell_price'],
            'start_dtm' => now(),
            'end_dtm' => null,
            'price_status' => 'A',
        ]);

        $taxId = $row['tax_id'] ?? null;
        if ($taxId !== null) {
            MerchantProductTaxes::create([
                'product_id' => $productId,
                'tax_id' => (int) $taxId,
                'status' => 'A',
            ]);
        }

        if (($row['tax_inclusive_flag'] ?? null) === 'Y' && $taxId !== null) {
            MerchantProductTaxInclusive::create([
                'product_id' => $productId,
                'inclusive_flag' => 'Y',
                'start_date_time' => now(),
                'end_date_time' => null,
                'current_status' => 'A',
            ]);
        }

        $batchTrim = isset($row['stock_batch']) && $row['stock_batch'] !== null ? trim((string) $row['stock_batch']) : '';
        $batchVal = $batchTrim === '' ? null : $batchTrim;

        if (! DB::table('merchant_product_branch')->where('product_id', $productId)->where('branch_id', $branchId)->exists()) {
            DB::table('merchant_product_branch')->insert([
                'product_id' => $productId,
                'branch_id' => $branchId,
                'status' => 1,
            ]);
        }

        $now = now()->format('Y-m-d H:i:s');

        $stockId = (int) DB::table('merchant_stock')->insertGetId([
            'reference_id' => $productId,
            'stock_type' => 'product',
            'stock_batch' => $batchVal,
            'stock_quantity' => $opening,
            'stock_quantity_in_hand' => $opening,
            'manufacturing_date' => null,
            'expiry_date' => $expiry ? $expiry->format('Y-m-d H:i:s') : null,
            'supplier_id' => null,
            'procurement_price' => null,
            'procurement_date' => null,
            'stock_status' => 'A',
            'created_dtm' => $now,
            'updated_dtm' => null,
        ]);

        DB::table('branch_stocks')->insert([
            'branch_id' => $branchId,
            'stock_id' => $stockId,
            'quantity' => $opening,
            'quantity_in_hand' => $opening,
        ]);

        DB::table('stock_transaction')->insert([
            'stock_id' => $stockId,
            'source_id' => null,
            'destination_id' => $branchId,
            'procured_by' => $this->actingUserId,
            'movement_type' => 'Imported',
            'stock_quantity' => $opening,
            'created_dtm' => $now,
            'updated_dtm' => null,
        ]);

        $tags = $row['tags'] ?? [];
        if (is_array($tags) && $tags !== []) {
            foreach ($tags as $tagName) {
                $tagName = trim((string) $tagName);
                if ($tagName === '') {
                    continue;
                }
                $key = mb_strtolower($tagName, 'UTF-8');
                if (! isset($tagMapLowerNameToId[$key])) {
                    $created = UniversalTags::create([
                        'company_id' => $this->companyId,
                        'module_id' => 1,
                        'tag_name' => $tagName,
                        'created_by' => $this->actingUserId,
                    ]);
                    $tagMapLowerNameToId[$key] = (int) $created->tag_id;
                }
                $tagId = $tagMapLowerNameToId[$key];
                UniversalTagsMapping::firstOrCreate([
                    'tag_id' => $tagId,
                    'resource_id' => $productId,
                    'resource_module_id' => 1,
                ]);
            }
        }
    }

    /**
     * @param  mixed  $raw
     */
    private function parseExpiry($raw): ?Carbon
    {
        if ($raw === null || $raw === '') {
            return null;
        }
        if (is_numeric($raw)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $raw));
            } catch (\Throwable $e) {
                report($e);
            }
        }
        try {
            return Carbon::parse((string) $raw);
        } catch (\Throwable $e) {
            report($e);

            return null;
        }
    }
}
