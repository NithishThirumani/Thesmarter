<?php

namespace App\Modules\AdminFeature\Http\Controllers;

use App\AppFeatures;
use App\Http\Controllers\Controller;
use App\Modules\AdminFeature\Http\Requests\FeatureStoreRequest;
use App\Modules\AdminFeature\Http\Requests\FeatureUpdateRequest;
use App\Modules\AdminFeature\Services\FeatureService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminFeatureController extends Controller
{
    private FeatureService $service;

    public function __construct(FeatureService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $filters = [
            'search' => $request->input('search'),
            'feature_status' => $request->input('feature_status', 'all'),
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

    public function show(int $id): JsonResponse
    {
        try {
            $feature = $this->service->getOne($id);
            return response()->json(['success' => true, 'data' => $feature], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Feature not found.'], 404);
        }
    }

    public function store(FeatureStoreRequest $request): JsonResponse
    {
        $feature = $this->service->create($request->validated());
        return response()->json(['success' => true, 'message' => 'Feature created.', 'data' => $feature], 201);
    }

    public function update(FeatureUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $feature = $this->service->update($id, $request->validated());
            return response()->json(['success' => true, 'message' => 'Feature updated.', 'data' => $feature], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Feature not found.'], 404);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);
            return response()->json(['success' => true, 'message' => 'Feature deleted.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Feature not found.'], 404);
        }
    }

    public function dropdowns(): JsonResponse
    {
        $list = AppFeatures::whereIn('feature_status', ['A', '1', 1, 'Active'])
            ->orderBy('feature_name')
            ->get();

        return response()->json(['success' => true, 'data' => $list], 200);
    }
}

