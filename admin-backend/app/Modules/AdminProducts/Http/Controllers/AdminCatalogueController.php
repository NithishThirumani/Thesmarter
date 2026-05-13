<?php

namespace App\Modules\AdminProducts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AdminProducts\Services\CatalogueAdminService;
use App\Services\AdminNotificationService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Tenant catalogues (merchant_catalogue) — admin list/create only.
 */
class AdminCatalogueController extends Controller
{
    private CatalogueAdminService $catalogueAdminService;

    public function __construct(CatalogueAdminService $catalogueAdminService)
    {
        $this->catalogueAdminService = $catalogueAdminService;
    }

    /**
     * GET /admin/catalogues?page=&per_page=&company_id=&catalogue_status=all|active|inactive&search=
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'company_id' => 'sometimes|integer|min:1',
            'catalogue_status' => 'sometimes|string|in:all,active,inactive',
            'search' => 'sometimes|string|max:120',
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        $filters = [
            'company_id' => $request->input('company_id'),
            'catalogue_status' => $request->input('catalogue_status', 'all'),
            'search' => $request->input('search', ''),
        ];
        $perPage = (int) $request->input('per_page', 15);

        $page = $this->catalogueAdminService->listPaginated($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $page->items(),
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }

    /**
     * POST /admin/catalogues { company_id, lob_id, catalogue_status? }
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $row = $this->catalogueAdminService->create($request->all());
        } catch (QueryException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Could not save catalogue — check constraints (company, LOB) or duplicated entry.',
            ], 422);
        }

        AdminNotificationService::record(
            'success',
            'Catalogue created',
            sprintf('Company #%d — catalogue #%d (LOB #%s).', (int) $row->company_id, (int) $row->catalogue_id, (string) $row->lob_id)
        );

        return response()->json([
            'success' => true,
            'data' => [
                'catalogue_id' => $row->catalogue_id,
                'company_id' => $row->company_id,
                'lob_id' => $row->lob_id,
                'catalogue_status' => $row->catalogue_status,
            ],
        ], 201);
    }
}
