<?php

namespace App\Modules\AdminDashboard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AdminDashboard\Services\PlatformDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use LogicException;

class AdminPlatformDashboardController extends Controller
{
    /** @var PlatformDashboardService */
    private $dashboard;

    public function __construct(PlatformDashboardService $dashboard)
    {
        $this->dashboard = $dashboard;
    }

    /**
     * GET /admin/dashboard/summary — KPI cards (supports date range + optional company_id).
     */
    public function summary(Request $request): JsonResponse
    {
        [$companyId, $from, $to] = $this->parseDashboardRequest($request);

        try {
            $range = $this->dashboard->normalizeDateRange($from, $to);
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $this->dashboard->summary($companyId, $range['from'], $range['to']),
        ]);
    }

    /** GET /admin/dashboard/companies — paginated tenant health metrics. */
    public function companies(Request $request): JsonResponse
    {
        [$scopedCompanyId] = $this->parseDashboardRequest($request);
        $perPage = (int) $request->input('per_page', 15);
        $perPage = min(max($perPage, 1), 100);
        $page = max(1, (int) $request->input('page', 1));

        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('company_status'),
        ];

        try {
            $paginator = $this->dashboard->companyInsightsPaginated($scopedCompanyId, $filters, $page, $perPage);

            return response()->json([
                'success' => true,
                'data' => $paginator->items(),
                'meta' => [
                    'current_page' => $paginator->currentPage(),
                    'last_page' => $paginator->lastPage(),
                    'per_page' => $paginator->perPage(),
                    'total' => $paginator->total(),
                ],
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json(['success' => false, 'message' => 'Dashboard companies query failed.'], 500);
        }
    }

    /** GET /admin/dashboard/growth — time-series (date range required / defaulted). */
    public function growth(Request $request): JsonResponse
    {
        [$companyId, $from, $to] = $this->parseDashboardRequest($request);

        try {
            $range = $this->dashboard->normalizeDateRange($from, $to);
            $payload = $this->dashboard->growth($companyId, $range['from'], $range['to']);
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => $payload]);
    }

    /** GET /admin/dashboard/uploads — upload proxy metric (products created density). */
    public function uploads(Request $request): JsonResponse
    {
        [$companyId, $from, $to] = $this->parseDashboardRequest($request);

        try {
            $range = $this->dashboard->normalizeDateRange($from, $to);
            $payload = $this->dashboard->uploadInsights($companyId, $range['from'], $range['to']);
        } catch (LogicException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'data' => $payload]);
    }

    /** GET /admin/dashboard/alerts */
    public function alerts(Request $request): JsonResponse
    {
        [$companyId,,] = $this->parseDashboardRequest($request);

        return response()->json([
            'success' => true,
            'data' => $this->dashboard->alerts($companyId, now()),
        ]);
    }

    /**
     * @return array{0: ?int, 1: ?string, 2: ?string}
     */
    private function parseDashboardRequest(Request $request): array
    {
        $raw = $request->input('company_id');
        $companyId = null;
        if ($raw !== null && $raw !== '') {
            $companyId = (int) $raw;
        }

        return [
            $companyId,
            $request->filled('date_from') ? (string) $request->input('date_from') : null,
            $request->filled('date_to') ? (string) $request->input('date_to') : null,
        ];
    }
}
