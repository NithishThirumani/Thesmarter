<?php

namespace App\Modules\AdminPaymentMethod\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AdminPaymentMethod\Http\Requests\PaymentMethodStoreRequest;
use App\Modules\AdminPaymentMethod\Http\Requests\PaymentMethodUpdateRequest;
use App\Modules\AdminPaymentMethod\Services\PaymentMethodAdminService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

class AdminPaymentMethodController extends Controller
{
    private PaymentMethodAdminService $service;

    public function __construct(PaymentMethodAdminService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $filters = [
            'search' => $request->input('search'),
            'payment_status' => $request->input('payment_status', 'all'),
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
            $method = $this->service->getOne($id);

            return response()->json(['success' => true, 'data' => $method], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Payment method not found.'], 404);
        }
    }

    public function store(PaymentMethodStoreRequest $request): JsonResponse
    {
        $method = $this->service->create($request->validated());

        return response()->json(['success' => true, 'message' => 'Payment method created.', 'data' => $method], 201);
    }

    public function update(PaymentMethodUpdateRequest $request, int $id): JsonResponse
    {
        try {
            $method = $this->service->update($id, $request->validated());

            return response()->json(['success' => true, 'message' => 'Payment method updated.', 'data' => $method], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Payment method not found.'], 404);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->service->delete($id);

            return response()->json(['success' => true, 'message' => 'Payment method deleted.'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Payment method not found.'], 404);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }
}
