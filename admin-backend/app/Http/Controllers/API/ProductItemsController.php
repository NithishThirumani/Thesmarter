<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductItemsController extends Controller
{
    /**
     * GET /api/v1/products?company_id=&branch_id=&lob_id=&search=&all_branches=
     * Returns active catalogue products visible for a company/branch.
     *
     * - When lob_id is set, catalogues with lob_id NULL still match (company-wide catalogues).
     * - When branch_id is set, products are limited to rows in merchant_product_branch for that
     *   branch or products with no branch rows. Bulk Excel imports attach one branch per row;
     *   if the app sends a different branch_id, the list is empty unless all_branches=1.
     */
    public function index(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|min:1',
            'branch_id' => 'nullable|integer|min:1',
            'lob_id' => 'nullable|integer|min:1',
            'all_branches' => 'nullable|boolean',
            'search' => 'nullable|string|max:120',
            'per_page' => 'nullable|integer|min:1|max:200',
            'page' => 'nullable|integer|min:1',
        ]);

        $companyId = (int) $validated['company_id'];
        $branchId = isset($validated['branch_id']) ? (int) $validated['branch_id'] : null;
        $lobId = isset($validated['lob_id']) ? (int) $validated['lob_id'] : null;
        $ignoreBranchScope = (bool) ($validated['all_branches'] ?? false);
        $search = isset($validated['search']) ? trim((string) $validated['search']) : '';
        $perPage = (int) ($validated['per_page'] ?? 50);

        $q = DB::table('merchant_catalogue_products as p')
            ->join('merchant_catalogue as c', 'c.catalogue_id', '=', 'p.catalogue_id')
            ->leftJoin('merchant_product_prices as pr', function ($join) {
                $join->on('pr.product_id', '=', 'p.product_id')
                    ->where(function ($w) {
                        $w->whereNull('pr.price_status')
                            ->orWhereRaw("TRIM(IFNULL(pr.price_status,'')) = ''")
                            ->orWhereRaw("UPPER(TRIM(IFNULL(pr.price_status,''))) IN ('A','1','ACTIVE','Y','YES','TRUE')");
                    });
            })
            ->leftJoin('merchant_product_branch as pb', 'pb.product_id', '=', 'p.product_id')
            ->where('c.company_id', $companyId)
            ->where(function ($w) {
                $w->whereNull('c.catalogue_status')
                    ->orWhereRaw("TRIM(IFNULL(c.catalogue_status,'')) = ''")
                    ->orWhereRaw("UPPER(TRIM(IFNULL(c.catalogue_status,''))) IN ('A','1','ACTIVE','Y','YES','TRUE')");
            })
            ->where(function ($w) {
                $w->whereNull('p.product_status')
                    ->orWhereRaw("TRIM(IFNULL(p.product_status,'')) = ''")
                    ->orWhereRaw("UPPER(TRIM(IFNULL(p.product_status,''))) IN ('A','1','ACTIVE','Y','YES','TRUE')");
            })
            ->select([
                'p.product_id',
                'p.catalogue_id',
                'c.lob_id',
                'p.product_name',
                'p.product_brand',
                'p.product_code',
                'p.product_logo',
                'p.quantity_in_hand',
                'pr.product_sell_price',
                'pr.product_cost_price',
            ])
            ->distinct()
            ->orderByDesc('p.product_id');

        // Include products with no branch row yet (legacy / partial imports) as well as rows for this branch.
        if ($branchId !== null && ! $ignoreBranchScope) {
            $q->where(function ($w) use ($branchId) {
                $w->whereNull('pb.product_id')
                    ->orWhere(function ($inner) use ($branchId) {
                        $inner->where('pb.branch_id', $branchId)
                            ->where(function ($s) {
                                $s->whereNull('pb.status')->orWhere('pb.status', 1);
                            });
                    });
            });
        }

        if ($lobId !== null) {
            $q->where(function ($w) use ($lobId) {
                $w->whereNull('c.lob_id')
                    ->orWhere('c.lob_id', $lobId);
            });
        }

        if ($search !== '') {
            $like = '%'.$search.'%';
            $q->where(function ($w) use ($like) {
                $w->where('p.product_name', 'like', $like)
                    ->orWhere('p.product_code', 'like', $like)
                    ->orWhere('p.product_brand', 'like', $like);
            });
        }

        $page = $q->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ], 200);
    }
}
