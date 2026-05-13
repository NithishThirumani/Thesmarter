<?php

namespace App\Modules\AdminDashboard\Services;

use App\CompanyDetail;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use LogicException;

/**
 * Read-only platform-wide analytics (multi-tenant). Optional company_id narrows aggregates to one tenant.
 */
final class PlatformDashboardService
{
    /** Typical active company_status in ERP (integer). */
    private const ACTIVE_STATUSES = [1, '1'];

    /** @return array<string, Carbon> */
    public function normalizeDateRange(?string $dateFromRaw, ?string $dateToRaw): array
    {
        try {
            $to = $dateToRaw !== null && $dateToRaw !== ''
                ? Carbon::parse($dateToRaw)->endOfDay()
                : Carbon::now()->endOfDay();
            $from = $dateFromRaw !== null && $dateFromRaw !== ''
                ? Carbon::parse($dateFromRaw)->startOfDay()
                : Carbon::now()->copy()->subDays(30)->startOfDay();
        } catch (\Throwable $e) {
            throw new LogicException('Invalid date_from or date_to.');
        }

        if ($from->gt($to)) {
            throw new LogicException('date_from must not be after date_to.');
        }

        return ['from' => $from, 'to' => $to];
    }

    /**
     * @return array<string, mixed>
     */
    public function summary(?int $companyId, Carbon $rangeFrom, Carbon $rangeTo): array
    {
        $companyTable = (new CompanyDetail())->getTable();

        if (! Schema::hasTable($companyTable)) {
            return [
                'total_companies' => 0,
                'active_companies' => 0,
                'inactive_companies' => 0,
                'new_companies_in_range' => null,
                'total_catalogues' => 0,
                'total_mapped_users' => null,
                'range' => [
                    'from' => $rangeFrom->toIso8601String(),
                    'to' => $rangeTo->toIso8601String(),
                ],
                'scoped_company_id' => $companyId,
            ];
        }

        $baseCompany = DB::table($companyTable);
        $this->applyCompanyFilter($baseCompany, $companyId);

        $totalCompanies = (clone $baseCompany)->count();

        $activeQuery = clone $baseCompany;
        $activeQuery->where(function ($w) use ($companyTable) {
            $w->whereIn($companyTable.'.company_status', self::ACTIVE_STATUSES)
                ->orWhereRaw('UPPER(TRIM(CAST('.$companyTable.'.company_status AS CHAR))) IN (\'A\',\'ACTIVE\')');
        });

        $activeCompanies = (clone $activeQuery)->count();

        $newInRangeQuery = clone $baseCompany;
        $createCol = Schema::hasColumn($companyTable, 'create_dtm') ? 'create_dtm' : null;
        if ($createCol !== null) {
            $newInRangeQuery->whereBetween($companyTable.'.'.$createCol, [$rangeFrom->toDateTimeString(), $rangeTo->toDateTimeString()]);
            $newCompaniesInRange = (int) $newInRangeQuery->count();
        } else {
            $newCompaniesInRange = null;
        }

        $inactiveCompanies = max(0, $totalCompanies - $activeCompanies);

        $totalCatalogues = 0;
        if (Schema::hasTable('merchant_catalogue')) {
            $catalogueCountQuery = DB::table('merchant_catalogue');
            $this->applyCompanyFilter($catalogueCountQuery, $companyId, 'company_id');
            $totalCatalogues = (int) $catalogueCountQuery->count();
        }

        return [
            'total_companies' => (int) $totalCompanies,
            'active_companies' => (int) $activeCompanies,
            'inactive_companies' => (int) $inactiveCompanies,
            'new_companies_in_range' => $newCompaniesInRange,
            'total_catalogues' => $totalCatalogues,
            'total_mapped_users' => $this->countMappedUsers($companyId),
            'range' => [
                'from' => $rangeFrom->toIso8601String(),
                'to' => $rangeTo->toIso8601String(),
            ],
            'scoped_company_id' => $companyId,
        ];
    }

    /**
     * @param  array{search?: ?string, status?: ?scalar}  $filters
     */
    public function companyInsightsPaginated(?int $scopedCompanyId, array $filters, int $page, int $perPage): LengthAwarePaginator
    {
        $companyTable = (new CompanyDetail())->getTable();

        $q = CompanyDetail::query()->select([
            $companyTable.'.company_id',
            $companyTable.'.company_name',
            $companyTable.'.company_status',
            $companyTable.'.create_dtm',
            $companyTable.'.updated_dtm',
        ]);

        if ($scopedCompanyId !== null) {
            $q->where($companyTable.'.company_id', $scopedCompanyId);
        }

        if (! empty($filters['search'])) {
            $s = '%'.$filters['search'].'%';
            $q->where(function ($inner) use ($s, $companyTable) {
                $inner->where($companyTable.'.company_name', 'like', $s);
                if (Schema::hasColumn($companyTable, 'company_code')) {
                    $inner->orWhere($companyTable.'.company_code', 'like', $s);
                }
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '' && $filters['status'] !== null) {
            $q->where($companyTable.'.company_status', $filters['status']);
        }

        $q->orderByDesc($companyTable.'.company_id');

        /** @var LengthAwarePaginator<int, CompanyDetail> $page */
        $page = $q->paginate($perPage, ['*'], 'page', $page);
        $ids = $page->getCollection()->pluck('company_id')->filter()->unique()->values()->all();

        if ($ids === []) {
            return $page;
        }

        $lastActivities = $this->lastActivityForCompanies($ids);
        $catalogueCounts = $this->countsByCompany('merchant_catalogue', 'catalogue_id', $ids);
        $productCounts = $this->productCountsForCompanies($ids);

        foreach ($page->getCollection() as $row) {
            $cid = (int) $row->company_id;
            $row->setAttribute('last_activity', $lastActivities[$cid] ?? null);
            $row->setAttribute('total_catalogues', (int) ($catalogueCounts[$cid] ?? 0));
            $row->setAttribute('total_products', (int) ($productCounts[$cid] ?? 0));
        }

        return $page;
    }

    /**
     * @return array<string, mixed>
     */
    public function growth(?int $companyId, Carbon $from, Carbon $to): array
    {
        if (! Schema::hasTable('company_detail')) {
            return [
                'companies_onboarded_by_day' => [],
                'products_created_by_day' => [],
                'stock_entries_by_day' => [],
                'scoped_company_id' => $companyId,
                'range' => ['from' => $from->toIso8601String(), 'to' => $to->toIso8601String()],
            ];
        }

        $companySql =
            'SELECT DATE(create_dtm) AS d, COUNT(*) AS cnt
             FROM company_detail
             WHERE create_dtm IS NOT NULL
               AND create_dtm BETWEEN ? AND ?
               '.($companyId !== null ? 'AND company_id = ? ' : '').'
             GROUP BY DATE(create_dtm)
             ORDER BY d';

        $companyBindings = $companyId !== null
            ? [$from->toDateTimeString(), $to->toDateTimeString(), $companyId]
            : [$from->toDateTimeString(), $to->toDateTimeString()];

        $companiesSeries = collect(DB::select($companySql, $companyBindings))
            ->map(static function ($r) {
                return ['date' => (string) $r->d, 'count' => (int) $r->cnt];
            })->values()->all();

        $productSeries = [];
        if (Schema::hasTable('merchant_catalogue_products') && Schema::hasTable('merchant_catalogue')) {
            $productSql =
                'SELECT DATE(p.created_dtm) AS d, COUNT(*) AS cnt
                 FROM merchant_catalogue_products AS p
                 INNER JOIN merchant_catalogue AS mc ON p.catalogue_id = mc.catalogue_id
                 WHERE p.created_dtm IS NOT NULL
                   AND p.created_dtm BETWEEN ? AND ?
                   '.($companyId !== null ? 'AND mc.company_id = ? ' : '').'
                 GROUP BY DATE(p.created_dtm)
                 ORDER BY d';

            $productBindings = $companyId !== null
                ? [$from->toDateTimeString(), $to->toDateTimeString(), $companyId]
                : [$from->toDateTimeString(), $to->toDateTimeString()];

            $productSeries = collect(DB::select($productSql, $productBindings))
                ->map(static function ($r) {
                    return ['date' => (string) $r->d, 'count' => (int) $r->cnt];
                })->values()->all();
        }

        $stockSeries = $this->stockCreatedSeries($companyId, $from, $to);

        return [
            'companies_onboarded_by_day' => $companiesSeries,
            'products_created_by_day' => $productSeries,
            'stock_entries_by_day' => $stockSeries,
            'scoped_company_id' => $companyId,
            'range' => ['from' => $from->toIso8601String(), 'to' => $to->toIso8601String()],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function uploadInsights(?int $companyId, Carbon $from, Carbon $to): array
    {
        if (! Schema::hasTable('merchant_catalogue_products') || ! Schema::hasTable('merchant_catalogue')) {
            return [
                'metric' => 'products_created_proxy',
                'note' => 'Product tables are not present in this database yet.',
                'total_events' => 0,
                'by_day' => [],
                'scoped_company_id' => $companyId,
                'range' => ['from' => $from->toIso8601String(), 'to' => $to->toIso8601String()],
            ];
        }

        $rows = DB::select(
            'SELECT DATE(p.created_dtm) AS d, COUNT(*) AS cnt
             FROM merchant_catalogue_products AS p
             INNER JOIN merchant_catalogue AS mc ON p.catalogue_id = mc.catalogue_id
             WHERE p.created_dtm IS NOT NULL
               AND p.created_dtm BETWEEN ? AND ?
               '.($companyId !== null ? 'AND mc.company_id = ? ' : '').'
             GROUP BY DATE(p.created_dtm)
             ORDER BY d',
            $companyId !== null
                ? [$from->toDateTimeString(), $to->toDateTimeString(), $companyId]
                : [$from->toDateTimeString(), $to->toDateTimeString()]
        );

        $byDay = collect($rows)->map(static function ($r) {
            return ['date' => (string) $r->d, 'products_added' => (int) $r->cnt];
        })->values()->all();

        $total = array_sum(array_column($byDay, 'products_added'));

        return [
            'metric' => 'products_created_proxy',
            'note' => 'No dedicated upload log table; counts reflect product rows created in the selected range.',
            'total_events' => (int) $total,
            'by_day' => $byDay,
            'scoped_company_id' => $companyId,
            'range' => ['from' => $from->toIso8601String(), 'to' => $to->toIso8601String()],
        ];
    }

    /**
     * @return array<int, array{type: string, severity: string, company_id: int|null, company_name: string|null, message: string, meta?: array<string, mixed>}>
     */
    public function alerts(?int $companyId, Carbon $asOf): array
    {
        $out = [];
        $companyTable = (new CompanyDetail())->getTable();
        $cutoff = $asOf->copy()->subDays(30);

        $noProducts = $this->companiesWithNoProducts($companyId);
        foreach ($noProducts as $row) {
            $out[] = [
                'type' => 'no_products',
                'severity' => 'warning',
                'company_id' => (int) $row->company_id,
                'company_name' => (string) $row->company_name,
                'message' => 'Company has no catalogue products yet.',
            ];
        }

        $noTaxes = $this->companiesWithNoTaxes($companyId);
        foreach ($noTaxes as $row) {
            $out[] = [
                'type' => 'no_taxes_configured',
                'severity' => 'info',
                'company_id' => (int) $row->company_id,
                'company_name' => (string) $row->company_name,
                'message' => 'No active tax_master rows configured for this company.',
            ];
        }

        $lastByCompany = $this->rollupLastEventsPerCompany();
        $activityCompanyIds = [];
        foreach ($lastByCompany as $r) {
            if ($companyId !== null && (int) $r->company_id !== $companyId) {
                continue;
            }
            $activityCompanyIds[(int) $r->company_id] = true;
            $companyRow = DB::table($companyTable)->where('company_id', $r->company_id)->first();
            if ($companyRow === null) {
                continue;
            }
            $evidence = Carbon::parse((string) $r->mx);
            if ($evidence->lt($cutoff)) {
                $out[] = [
                    'type' => 'inactive_30d',
                    'severity' => 'warning',
                    'company_id' => (int) $companyRow->company_id,
                    'company_name' => (string) $companyRow->company_name,
                    'message' => 'No ERP activity detected in the last 30 days.',
                    'meta' => [
                        'last_activity' => $evidence->toIso8601String(),
                    ],
                ];
            }
        }

        // Tenants missing from rollup (never touched linked tables): fall back to company_detail timestamps only.
        $missing = DB::table($companyTable)
            ->select('company_id', 'company_name', 'updated_dtm', 'create_dtm')
            ->when($companyId !== null, static function ($q) use ($companyId): void {
                $q->where('company_id', $companyId);
            })
            ->orderByDesc('company_id');

        foreach ($missing->cursor() as $c) {
            if (isset($activityCompanyIds[(int) $c->company_id])) {
                continue;
            }
            $fallbackStr = $c->updated_dtm ?? $c->create_dtm ?? null;
            if ($fallbackStr === null) {
                continue;
            }
            $fallback = Carbon::parse((string) $fallbackStr);
            if ($fallback->lt($cutoff)) {
                $out[] = [
                    'type' => 'inactive_30d',
                    'severity' => 'warning',
                    'company_id' => (int) $c->company_id,
                    'company_name' => (string) $c->company_name,
                    'message' => 'No ERP activity detected in the last 30 days.',
                    'meta' => ['last_activity' => null],
                ];
            }
        }

        usort($out, static fn ($a, $b) => strcmp((string) $a['type'], (string) $b['type']));

        return $out;
    }

    /** @param mixed $q  Query builder (Illuminate) */
    private function applyCompanyFilter($q, ?int $companyId, string $column = 'company_id'): void
    {
        if ($companyId !== null) {
            $q->where($column, '=', $companyId);
        }
    }

    /** @param list<int|string> $ids */
    /** @return array<int, string|null> */
    private function lastActivityForCompanies(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $companyTable = (new CompanyDetail())->getTable();

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $inner = $this->rollupLastEventsUnionSqlBody();
        $sql =
            'SELECT la.company_id, MAX(la.mx) AS mx
             FROM ('.$inner.') AS la
             WHERE la.company_id IN ('.$placeholders.')
             GROUP BY la.company_id';

        /** @phpstan-ignore-next-line */
        $rows = DB::select($sql, $ids);

        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->company_id] = isset($r->mx) ? (string) $r->mx : null;
        }

        foreach ($ids as $id) {
            $cid = (int) $id;
            if (! array_key_exists($cid, $map)) {
                $cd = DB::table($companyTable)->where('company_id', $cid)->select('updated_dtm', 'create_dtm')->first();
                $fallback = null;
                if ($cd !== null) {
                    $fallback = ($cd->updated_dtm ?? $cd->create_dtm) !== null
                        ? (string) ($cd->updated_dtm ?? $cd->create_dtm)
                        : null;
                }
                $map[$cid] = $fallback;
            }
        }

        return $map;
    }

    /**
     * @return array<int|string, mixed>
     */
    private function rollupLastEventsPerCompany(): array
    {
        return DB::select(
            'SELECT la.company_id, MAX(la.mx) AS mx
             FROM ('.$this->rollupLastEventsUnionSqlBody().') AS la
             GROUP BY la.company_id'
        );
    }

    /** Raw UNION ALL body (no wrapping parens outside). */
    private function rollupLastEventsUnionSqlBody(): string
    {
        $companyTable = (new CompanyDetail())->getTable();

        $arms = [
            'SELECT company_id, updated_dtm AS mx FROM '.$companyTable.' WHERE updated_dtm IS NOT NULL',
        ];

        if (Schema::hasTable('branch_detail')) {
            $arms[] = 'SELECT company_id, updated_dtm AS mx FROM branch_detail WHERE updated_dtm IS NOT NULL';
        }

        if (Schema::hasTable('merchant_catalogue_products') && Schema::hasTable('merchant_catalogue')) {
            $arms[] =
                'SELECT mc.company_id, p.updated_dtm AS mx
                 FROM merchant_catalogue_products AS p
                 INNER JOIN merchant_catalogue AS mc ON mc.catalogue_id = p.catalogue_id
                 WHERE p.updated_dtm IS NOT NULL';
        }

        if (Schema::hasTable('order_detail')) {
            $arms[] = 'SELECT company_id, updated_dtm AS mx FROM order_detail WHERE updated_dtm IS NOT NULL';
        }

        return implode(' UNION ALL ', $arms);
    }

    /** @param list<int|string> $companyIds */
    /** @return array<int, int> */
    private function countsByCompany(string $table, string $pk, array $companyIds): array
    {
        if ($companyIds === [] || ! Schema::hasTable($table)) {
            return [];
        }

        return DB::table($table)
            ->whereIn('company_id', $companyIds)
            ->selectRaw('company_id, COUNT('.$pk.') AS cnt')
            ->groupBy('company_id')
            ->get()
            ->mapWithKeys(static function ($row) {
                return [(int) $row->company_id => (int) $row->cnt];
            })->all();
    }

    /** @param list<int|string> $companyIds */
    /** @return array<int, int> */
    private function productCountsForCompanies(array $companyIds): array
    {
        if ($companyIds === [] || ! Schema::hasTable('merchant_catalogue_products') || ! Schema::hasTable('merchant_catalogue')) {
            return [];
        }

        return DB::table('merchant_catalogue_products AS p')
            ->join('merchant_catalogue AS mc', 'p.catalogue_id', '=', 'mc.catalogue_id')
            ->whereIn('mc.company_id', $companyIds)
            ->selectRaw('mc.company_id, COUNT(p.product_id) AS cnt')
            ->groupBy('mc.company_id')
            ->get()
            ->mapWithKeys(static function ($row) {
                return [(int) $row->company_id => (int) $row->cnt];
            })->all();
    }

    private function countMappedUsers(?int $companyId): ?int
    {
        if (! Schema::hasTable('user_company_roles_v2')) {
            return Schema::hasTable('user_detail') ? (int) DB::table('user_detail')->count() : null;
        }

        $q = DB::table('user_company_roles_v2');
        $this->applyCompanyFilter($q, $companyId);

        $row = $q->selectRaw('COUNT(DISTINCT user_id) AS u')->first();

        return $row !== null ? (int) ($row->u ?? 0) : 0;
    }

    /**
     * @return list<object{company_id: int|string, company_name: mixed}>
     */
    private function companiesWithNoProducts(?int $companyId): array
    {
        if (! Schema::hasTable('merchant_catalogue') || ! Schema::hasTable('merchant_catalogue_products')) {
            return [];
        }

        $companyTable = (new CompanyDetail())->getTable();

        $q = DB::table($companyTable.' AS c')
            ->select('c.company_id', 'c.company_name')
            ->whereNotExists(function ($exists): void {
                $exists->select(DB::raw(1))
                    ->from('merchant_catalogue_products AS p')
                    ->join('merchant_catalogue AS mc', 'p.catalogue_id', '=', 'mc.catalogue_id')
                    ->whereColumn('mc.company_id', 'c.company_id')
                    ->limit(1);
            });

        $this->applyCompanyFilter($q, $companyId, 'c.company_id');

        return $q->limit(50)->orderByDesc('c.company_id')->get()->all();
    }

    /**
     * @return list<object{company_id: int|string, company_name: mixed}>
     */
    private function companiesWithNoTaxes(?int $companyId): array
    {
        $taxHasStatus = Schema::hasTable('tax_master') && Schema::hasColumn('tax_master', 'tax_status');

        $q = DB::table('company_detail AS c')
            ->select('c.company_id', 'c.company_name')
            ->whereNotExists(function ($exists) use ($taxHasStatus): void {
                $exists->select(DB::raw(1))
                    ->from('tax_master AS t')
                    ->whereColumn('t.company_id', 'c.company_id');
                if ($taxHasStatus) {
                    $exists->whereIn('t.tax_status', ['A', '1', 'a', 1]);
                }
                $exists->limit(1);
            });

        $this->applyCompanyFilter($q, $companyId, 'c.company_id');

        return $q->limit(50)->orderByDesc('c.company_id')->get()->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function stockCreatedSeries(?int $companyId, Carbon $from, Carbon $to): array
    {
        if (! Schema::hasTable('merchant_stock')
            || ! Schema::hasTable('branch_stocks')
            || ! Schema::hasTable('branch_detail')) {
            return [];
        }

        $sql =
            'SELECT DATE(ms.created_dtm) AS d, COUNT(*) AS cnt
             FROM merchant_stock ms
             INNER JOIN branch_stocks bs ON bs.stock_id = ms.stock_id
             INNER JOIN branch_detail bd ON bd.branch_id = bs.branch_id
             WHERE ms.created_dtm IS NOT NULL
               AND ms.created_dtm BETWEEN ? AND ?
               '.($companyId !== null ? 'AND bd.company_id = ? ' : '').'
             GROUP BY DATE(ms.created_dtm)
             ORDER BY d';

        $bindings = $companyId !== null
            ? [$from->toDateTimeString(), $to->toDateTimeString(), $companyId]
            : [$from->toDateTimeString(), $to->toDateTimeString()];

        return collect(DB::select($sql, $bindings))->map(static function ($r) {
            return ['date' => (string) $r->d, 'count' => (int) $r->cnt];
        })->values()->all();
    }
}
