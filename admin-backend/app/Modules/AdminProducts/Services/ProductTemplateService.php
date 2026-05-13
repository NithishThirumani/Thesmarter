<?php

namespace App\Modules\AdminProducts\Services;

use App\BranchDetail;
use App\CompanyDetail;
use App\MerchantCatalogue;
use App\Modules\AdminProducts\Exceptions\ProductTemplateException;
use App\Modules\AdminProducts\Support\ProductBulkUploadTemplateBuilder;
use App\TaxMaster;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

/**
 * Company-scoped bulk product upload Excel template (read-only on tax_master, branch_detail, company & catalogue metadata).
 */
class ProductTemplateService
{
    /**
     * Tax + branch counts for bulk template (does not require catalogues to exist).
     */
    public function bulkUploadPrerequisites(int $companyId): array
    {
        if (! CompanyDetail::query()->whereKey($companyId)->exists()) {
            return [
                'company_exists' => false,
                'eligible_for_bulk_template' => false,
                'tax_count' => 0,
                'branch_count' => 0,
            ];
        }

        $taxCount = $this->activeTaxesQuery($companyId)->count();
        $branchCount = $this->activeBranchesQuery($companyId)->count();

        return [
            'company_exists' => true,
            'eligible_for_bulk_template' => $branchCount > 0,
            'tax_count' => $taxCount,
            'branch_count' => $branchCount,
        ];
    }

    public function readiness(int $companyId): array
    {
        $pre = $this->bulkUploadPrerequisites($companyId);

        if (! $pre['company_exists']) {
            return [
                'eligible' => false,
                'tax_count' => 0,
                'branch_count' => 0,
                'catalogue_count' => 0,
                'catalogues' => [],
                'errors' => ['Company not found.'],
            ];
        }

        $catalogues = MerchantCatalogue::query()
            ->where('company_id', $companyId)
            ->orderBy('catalogue_id')
            ->get(['catalogue_id', 'company_id', 'lob_id', 'catalogue_status'])
            ->map(static function ($c) {
                return $c->only(['catalogue_id', 'company_id', 'lob_id', 'catalogue_status']);
            })
            ->values()
            ->all();

        $errors = [];
        if ($pre['branch_count'] === 0) {
            $errors[] = 'No branches configured.';
        }
        if (count($catalogues) === 0) {
            $errors[] = 'No catalogues configured for company.';
        }

        return [
            'eligible' => count($errors) === 0,
            'tax_count' => $pre['tax_count'],
            'branch_count' => $pre['branch_count'],
            'catalogue_count' => count($catalogues),
            'catalogues' => $catalogues,
            'errors' => $errors,
        ];
    }

    public function generateTemplate(int $companyId, int $catalogueId): Spreadsheet
    {
        if (! CompanyDetail::query()->whereKey($companyId)->exists()) {
            throw new ProductTemplateException('Company not found.', 404);
        }

        $catalogue = MerchantCatalogue::query()
            ->whereKey($catalogueId)
            ->first();
        if (! $catalogue || (int) $catalogue->company_id !== $companyId) {
            throw new ProductTemplateException('Catalogue not found for this company.', 422);
        }

        $taxes = $this->activeTaxesQuery($companyId)->orderBy('tax_id')->get(['tax_id', 'tax_name']);
        $branches = $this->activeBranchesQuery($companyId)->orderBy('branch_id')->get(['branch_id', 'branch_name']);

        if ($branches->isEmpty()) {
            throw new ProductTemplateException('No branches configured.', 422);
        }

        $taxRows = $taxes->map(static function ($t) {
            return ['tax_id' => $t->tax_id, 'tax_name' => $t->tax_name];
        })->all();
        $branchRows = $branches->map(static function ($b) {
            return ['branch_id' => $b->branch_id, 'branch_name' => $b->branch_name];
        })->all();

        return (new ProductBulkUploadTemplateBuilder)->build($taxRows, $branchRows, $companyId, $catalogueId);
    }

    private function activeTaxesQuery(int $companyId): \Illuminate\Database\Eloquent\Builder
    {
        $q = TaxMaster::query()->where('company_id', $companyId);
        if (Schema::hasColumn('tax_master', 'tax_status')) {
            $q->where('tax_status', 'A');
        }

        return $q;
    }

    private function activeBranchesQuery(int $companyId): \Illuminate\Database\Eloquent\Builder
    {
        return BranchDetail::query()
            ->where('company_id', $companyId)
            ->where(function ($inner) {
                $inner
                    ->whereIn('branch_status', [1, '1'])
                    ->orWhereIn('branch_status', ['A', 'a']);
            });
    }
}
