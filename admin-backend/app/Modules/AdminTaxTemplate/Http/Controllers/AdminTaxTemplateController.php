<?php

namespace App\Modules\AdminTaxTemplate\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AdminTaxTemplate\Services\TaxTemplateAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AdminTaxTemplateController extends Controller
{
    private const REGION_TYPES = ['COUNTRY', 'STATE', 'CITY'];

    private const TAX_TYPES = ['GST', 'VAT', 'SALES_TAX'];

    private const APP_TYPES = ['FLAT', 'INTRA_STATE', 'INTER_STATE', 'LOCATION_BASED'];

    private TaxTemplateAdminService $service;

    public function __construct(TaxTemplateAdminService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /admin/tax-template?country_code=&region_code=&per_page=
     */
    public function index(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tax_master_template')) {
            return response()->json(['success' => true, 'data' => [], 'meta' => null], 200);
        }

        $perPage = max(1, min(100, (int) $request->query('per_page', 50)));

        $filters = [];
        if ($request->filled('country_code')) {
            $filters['country_code'] = (string) $request->query('country_code');
        }
        if ($request->has('region_code')) {
            $filters['region_code'] = $request->query('region_code');
        }

        $paginator = $this->service->list($filters, $perPage);

        return response()->json([
            'success' => true,
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ], 200);
    }

    public function show(int $id): JsonResponse
    {
        if (! Schema::hasTable('tax_master_template')) {
            return response()->json(['success' => false, 'message' => 'Tax templates unavailable.'], 503);
        }
        try {
            $row = $this->service->getOne($id);

            return response()->json(['success' => true, 'data' => $row], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Tax template not found.'], 404);
        }
    }

    /**
     * POST /admin/tax-template
     */
    public function store(Request $request): JsonResponse
    {
        if (! Schema::hasTable('tax_master_template')) {
            return response()->json(['success' => false, 'message' => 'Migration not applied.'], 503);
        }

        $v = Validator::make($request->all(), [
            'country_code' => 'required|string|size:2',
            'region_type' => ['sometimes', 'string', Rule::in(self::REGION_TYPES)],
            'region_code' => 'nullable|string|max:20',
            'tax_type' => ['sometimes', 'string', Rule::in(self::TAX_TYPES)],
            'applicability_type' => ['sometimes', 'string', Rule::in(self::APP_TYPES)],
            'tax_name' => 'required|string|max:255',
            'version' => 'sometimes|integer|min:1',
            'is_active' => 'sometimes|boolean',
            'components' => 'required|array|min:1',
            'components.*.component_name' => 'required|string|max:255',
            'components.*.tax_value' => 'sometimes|nullable|numeric|gt:0',
            'components.*.tax_start_date' => 'sometimes|nullable|date',
            'components.*.tax_end_date' => 'sometimes|nullable|date',
            'components.*.details' => 'sometimes|array|min:1',
            'components.*.details.*.tax_value' => 'sometimes|required|numeric|gt:0',
            'components.*.details.*.tax_start_date' => 'sometimes|required_with:components.*.details|date',
            'components.*.details.*.tax_end_date' => 'sometimes|nullable|date',
        ]);
        $v->after(function ($validator) use ($request) {
            foreach ((array) $request->input('components', []) as $idx => $comp) {
                $hasDetails = isset($comp['details']) && is_array($comp['details']) && count($comp['details']) > 0;
                $hasFlat = isset($comp['tax_value']);
                if (! $hasDetails && ! $hasFlat) {
                    $validator->errors()->add(
                        'components.'.$idx,
                        'Each component must include either tax_value or a non-empty details[] array.'
                    );
                }
                if ($hasDetails && isset($comp['tax_value'])) {
                    $validator->errors()->add(
                        'components.'.$idx,
                        'Provide either flat tax_value or details[], not both.'
                    );
                }
            }
        });
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $v->errors()], 422);
        }

        try {
            $created = $this->service->create($v->validated());

            return response()->json(['success' => true, 'message' => 'Tax template created.', 'data' => $created], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * PUT /admin/tax-template/{id}
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if (! Schema::hasTable('tax_master_template')) {
            return response()->json(['success' => false, 'message' => 'Migration not applied.'], 503);
        }

        $v = Validator::make($request->all(), [
            'country_code' => 'sometimes|string|size:2',
            'region_type' => ['sometimes', 'string', Rule::in(self::REGION_TYPES)],
            'region_code' => 'nullable|string|max:20',
            'tax_type' => ['sometimes', 'string', Rule::in(self::TAX_TYPES)],
            'applicability_type' => ['sometimes', 'string', Rule::in(self::APP_TYPES)],
            'tax_name' => 'sometimes|string|max:255',
            'version' => 'sometimes|integer|min:1',
            'is_active' => 'sometimes|boolean',
            'components' => 'sometimes|array|min:1',
            'components.*.component_name' => 'required_with:components|string|max:255',
            'components.*.tax_value' => 'sometimes|nullable|numeric|gt:0',
            'components.*.tax_start_date' => 'sometimes|nullable|date',
            'components.*.tax_end_date' => 'sometimes|nullable|date',
            'components.*.details' => 'sometimes|array|min:1',
            'components.*.details.*.tax_value' => 'sometimes|required|numeric|gt:0',
            'components.*.details.*.tax_start_date' => 'sometimes|required_with:components.*.details|date',
            'components.*.details.*.tax_end_date' => 'sometimes|nullable|date',
        ]);
        $v->after(function ($validator) use ($request) {
            if (! is_array($request->input('components'))) {
                return;
            }
            foreach ((array) $request->input('components', []) as $idx => $comp) {
                $hasDetails = isset($comp['details']) && is_array($comp['details']) && count($comp['details']) > 0;
                $hasFlat = isset($comp['tax_value']);
                if (! $hasDetails && ! $hasFlat) {
                    $validator->errors()->add(
                        'components.'.$idx,
                        'Each component must include either tax_value or a non-empty details[] array.'
                    );
                }
                if ($hasDetails && isset($comp['tax_value'])) {
                    $validator->errors()->add(
                        'components.'.$idx,
                        'Provide either flat tax_value or details[], not both.'
                    );
                }
            }
        });
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $v->errors()], 422);
        }

        try {
            $updated = $this->service->update($id, $v->validated());

            return response()->json(['success' => true, 'message' => 'Tax template updated.', 'data' => $updated], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Tax template not found.'], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\InvalidArgumentException|\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * POST /admin/tax-template/{id}/deactivate — soft deactivate
     */
    public function deactivate(int $id): JsonResponse
    {
        if (! Schema::hasTable('tax_master_template')) {
            return response()->json(['success' => false, 'message' => 'Migration not applied.'], 503);
        }
        try {
            $this->service->deactivate($id);

            return response()->json(['success' => true, 'message' => 'Tax template deactivated.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Tax template not found.'], 404);
        }
    }

    /**
     * DELETE /admin/tax-template/{id}
     */
    public function destroy(int $id): JsonResponse
    {
        if (! Schema::hasTable('tax_master_template')) {
            return response()->json(['success' => false, 'message' => 'Migration not applied.'], 503);
        }
        try {
            $this->service->destroy($id);

            return response()->json(['success' => true, 'message' => 'Tax template deleted.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Tax template not found.'], 404);
        }
    }
}
