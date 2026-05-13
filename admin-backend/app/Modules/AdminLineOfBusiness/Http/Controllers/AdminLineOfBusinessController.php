<?php

namespace App\Modules\AdminLineOfBusiness\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AdminLineOfBusiness\Services\LineOfBusinessService;
use App\Modules\AdminLineOfBusiness\Http\Requests\LineOfBusinessStoreRequest;
use App\Modules\AdminLineOfBusiness\Http\Requests\LineOfBusinessUpdateRequest;
use App\LineOfBusiness;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLineOfBusinessController extends Controller
{
    private LineOfBusinessService $service;

    public function __construct(LineOfBusinessService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /admin/line-of-business
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $filters = [
            'search' => $request->input('search'),
            'lob_status' => $request->input('lob_status', 'all'),
        ];

        $list = $this->service->list($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $list->items(),
            'meta' => [
                'current_page' => $list->currentPage(),
                'last_page' => $list->lastPage(),
                'per_page' => $list->perPage(),
                'total' => $list->total(),
            ],
        ], 200);
    }

    /**
     * GET /admin/line-of-business/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $lob = $this->service->getOne($id);
            return response()->json(['success' => true, 'data' => $lob], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Line of business not found.'], 404);
        }
    }

    /**
     * POST /admin/line-of-business
     */
    public function store(LineOfBusinessStoreRequest $request): JsonResponse
    {
        $lob = $this->service->create($request->validated());
        return response()->json(['success' => true, 'message' => 'Line of business created.', 'data' => $lob], 201);
    }

    /**
     * PUT /admin/line-of-business/{id}
     */
    public function update(LineOfBusinessUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $lob = $this->service->update($id, $request->validated());
            return response()->json(['success' => true, 'message' => 'Line of business updated.', 'data' => $lob], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Line of business not found.'], 404);
        }
    }

    /**
     * DELETE /admin/line-of-business/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);
            return response()->json(['success' => true, 'message' => 'Line of business deleted.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Line of business not found.'], 404);
        }
    }

    /**
     * GET /admin/line-of-business/dropdowns
     */
    public function dropdowns(): JsonResponse
    {
        // Keep it simple: active records only.
        $list = LineOfBusiness::whereIn('lob_status', ['A', 1, 'Active'])->orderBy('lob_name')->get();
        return response()->json(['success' => true, 'data' => $list], 200);
    }
}

