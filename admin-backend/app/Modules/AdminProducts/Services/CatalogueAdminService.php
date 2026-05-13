<?php

namespace App\Modules\AdminProducts\Services;

use App\MerchantCatalogue;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CatalogueAdminService
{
    /** @var ProductTemplateService */
    private $productTemplateService;

    public function __construct(ProductTemplateService $productTemplateService)
    {
        $this->productTemplateService = $productTemplateService;
    }

    /**
     * Paginated catalogue list with company / LOB names and template-download eligibility per company.
     *
     * @param array<string, mixed> $filters keys: company_id (int|null), catalogue_status ('active'|'inactive'|'all'|''), search (partial company name / catalogue id)
     */
    public function listPaginated(array $filters, int $perPage): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 100);
        $companyIdFilter = ! empty($filters['company_id']) ? (int) $filters['company_id'] : null;
        $statusFilter = isset($filters['catalogue_status']) ? (string) $filters['catalogue_status'] : 'all';
        $search = isset($filters['search']) ? trim((string) $filters['search']) : '';

        $q = MerchantCatalogue::query()
            ->select([
                'merchant_catalogue.catalogue_id',
                'merchant_catalogue.company_id',
                'merchant_catalogue.lob_id',
                'merchant_catalogue.catalogue_status',
            ])
            ->with([
                'company' => static function ($rel) {
                    $rel->select('company_id', 'company_name');
                },
                'lineOfBusiness' => static function ($rel) {
                    $rel->select('lob_id', 'lob_name', 'lob_status');
                },
            ])
            ->orderByDesc('merchant_catalogue.catalogue_id');

        if ($companyIdFilter) {
            $q->where('merchant_catalogue.company_id', $companyIdFilter);
        }

        if ($search !== '') {
            if (ctype_digit($search)) {
                $q->where('merchant_catalogue.catalogue_id', (int) $search);
            } else {
                $q->whereHas('company', static function ($c) use ($search) {
                    $c->where('company_name', 'LIKE', '%'.$search.'%');
                });
            }
        }

        $this->applyCatalogueListingStatusScope($q, $statusFilter);

        /** @var LengthAwarePaginator $page */
        $page = $q->paginate($perPage);

        $memo = [];
        $items = $page->getCollection()->map(function (MerchantCatalogue $row) use (&$memo) {
            $cid = (int) $row->company_id;
            if (! isset($memo[$cid])) {
                $memo[$cid] = $this->productTemplateService->bulkUploadPrerequisites($cid);
            }
            $pre = $memo[$cid];
            $isActive = self::isCatalogueStatusActive($row->catalogue_status);

            return [
                'catalogue_id' => (int) $row->catalogue_id,
                'company_id' => $cid,
                'company_name' => $row->company ? (string) $row->company->company_name : null,
                'lob_id' => $row->lob_id !== null ? (int) $row->lob_id : null,
                'lob_name' => $row->lineOfBusiness ? (string) $row->lineOfBusiness->lob_name : null,
                'lob_status' => $row->lineOfBusiness ? $row->lineOfBusiness->lob_status : null,
                'catalogue_status_raw' => $row->catalogue_status,
                'catalogue_active' => $isActive,
                'can_download_product_template' => $pre['eligible_for_bulk_template'] && $pre['company_exists'],
                'tax_count' => $pre['tax_count'],
                'branch_count' => $pre['branch_count'],
            ];
        });

        $page->setCollection($items);

        return $page;
    }

    /** @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query */
    private function applyCatalogueListingStatusScope($query, string $statusFilter): void
    {
        if ($statusFilter === 'active') {
            $query->where(static function ($w) {
                $w->whereNull('merchant_catalogue.catalogue_status')
                    ->orWhereRaw("TRIM(merchant_catalogue.catalogue_status) = ''")
                    ->orWhereIn(DB::raw('UPPER(TRIM(merchant_catalogue.catalogue_status))'), ['A', '1', 'ACTIVE', 'Y', 'YES', 'TRUE']);
            });

            return;
        }

        if ($statusFilter === 'inactive') {
            $query->whereRaw("UPPER(TRIM(IFNULL(merchant_catalogue.catalogue_status,''))) IN ('I','INACTIVE','0','N')");
        }
    }

    /**
     * @param array<string, mixed> $input
     */
    public function create(array $input): MerchantCatalogue
    {
        $v = Validator::make($input, [
            'company_id' => 'required|integer|exists:company_detail,company_id',
            'lob_id' => 'required|integer|exists:line_of_business,lob_id',
            'catalogue_status' => 'nullable|string|max:32',
        ])->validate();

        $status = isset($v['catalogue_status']) && trim((string) $v['catalogue_status']) !== ''
            ? trim((string) $v['catalogue_status'])
            : 'A';

        return MerchantCatalogue::query()->create([
            'company_id' => (int) $v['company_id'],
            'lob_id' => (int) $v['lob_id'],
            'catalogue_status' => $status,
        ]);
    }

    /**
     * Company has taxes & branches configured (template can be generated for any catalogue row of that tenant).
     */
    public static function isCatalogueStatusActive($catalogueStatus): bool
    {
        if ($catalogueStatus === null || $catalogueStatus === '') {
            return true;
        }

        $s = is_string($catalogueStatus)
            ? strtoupper(trim($catalogueStatus))
            : (string) $catalogueStatus;

        if (in_array($s, ['A', '1', 'ACTIVE', 'Y', 'YES', 'TRUE'], true)) {
            return true;
        }

        if ($s === 'I' || $s === '0' || strtoupper((string) $catalogueStatus) === 'INACTIVE') {
            return false;
        }

        if ($catalogueStatus === true || $catalogueStatus === 1 || $catalogueStatus === '1') {
            return true;
        }

        return ! in_array($s, ['N', 'NO', 'INACTIVE'], true);
    }
}
