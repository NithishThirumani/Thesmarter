<?php

namespace App\Modules\AdminCompany\Http\Controllers;

use App\CompanyDetail;
use App\Http\Controllers\Controller;
use App\Modules\AdminCompany\Exceptions\ExecutiveConflictNeedsConfirmation;
use App\Modules\AdminCompany\Http\Requests\ExecutiveStoreRequest;
use App\Modules\AdminCompany\Http\Requests\ExecutiveUpdateRequest;
use App\Modules\AdminCompany\Repositories\ExecutiveMappingRepository;
use App\Modules\AdminCompany\Services\SuperUserAvatarStorageService;
use App\Modules\AdminCompany\Services\SuperUserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SuperUserController extends Controller
{
    /**
     * GET /api/companies/{company_id}/super-users/mobile-check ?mobile=
     */
    public function mobileCheck(Request $request, int $company_id, SuperUserService $superUserService, ExecutiveMappingRepository $executiveMappings): JsonResponse
    {
        abort_unless($executiveMappings->companyExists((int) $company_id), 404);

        $validated = Validator::make($request->query(), [
            'mobile' => 'required|string|max:32',
        ])->validate();

        try {
            $data = $superUserService->checkMobileAvailability((int) $company_id, $validated['mobile']);
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            return response()->json([
                'success' => false,
                'message' => $first ?: 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    /**
     * GET /api/companies/{company_id}/super-users/modules
     */
    public function modulesForCompany(int $company_id, SuperUserService $superUserService, ExecutiveMappingRepository $executiveMappings): JsonResponse
    {
        abort_unless($executiveMappings->companyExists((int) $company_id), 404);

        try {
            $items = $superUserService->listExecutiveModules((int) $company_id);
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            return response()->json([
                'success' => false,
                'message' => $first ?: 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'data' => $items,
        ], 200);
    }

    /**
     * POST /api/companies/{company_id}/super-users/{user_id}/resend-welcome (legacy path)
     */
    public function resendWelcome(int $company_id, int $user_id, SuperUserService $superUserService, ExecutiveMappingRepository $executiveMappings): JsonResponse
    {
        return $this->pinMailResponse($company_id, $user_id, $superUserService, $executiveMappings);
    }

    /**
     * POST /api/companies/{company_id}/super-users/{user_id}/reset-pin
     */
    public function resetPin(int $company_id, int $user_id, SuperUserService $superUserService, ExecutiveMappingRepository $executiveMappings): JsonResponse
    {
        return $this->pinMailResponse($company_id, $user_id, $superUserService, $executiveMappings);
    }

    /**
     * POST /api/companies/{company_id}/super-users/{user_id}/resend-pin
     */
    public function resendPin(int $company_id, int $user_id, SuperUserService $superUserService, ExecutiveMappingRepository $executiveMappings): JsonResponse
    {
        return $this->pinMailResponse($company_id, $user_id, $superUserService, $executiveMappings);
    }

    private function pinMailResponse(int $company_id, int $user_id, SuperUserService $superUserService, ExecutiveMappingRepository $executiveMappings): JsonResponse
    {
        abort_unless($executiveMappings->companyExists((int) $company_id), 404);
        abort_if(
            ! $executiveMappings->activeExecutiveMappingExists((int) $company_id, (int) $user_id),
            404
        );

        try {
            $superUserService->resetExecutivePin((int) $company_id, (int) $user_id);
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            return response()->json([
                'success' => false,
                'message' => $first ?: 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'A new PIN has been generated and emailed.',
        ], 200);
    }

    /**
     * PATCH /api/companies/{company_id}/super-users/{user_id}/reactivate
     */
    public function reactivate(int $company_id, int $user_id, SuperUserService $superUserService, ExecutiveMappingRepository $executiveMappings): JsonResponse
    {
        abort_unless($executiveMappings->companyExists((int) $company_id), 404);

        abort_if(
            ! $executiveMappings->executiveMappingExists((int) $company_id, (int) $user_id),
            404
        );

        try {
            $superUserService->reactivateExecutive((int) $company_id, (int) $user_id);
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            return response()->json([
                'success' => false,
                'message' => $first ?: 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Executive reactivated for this company.',
        ], 200);
    }

    /**
     * GET /api/admin/super-users ?company_id=&search=&status=&page=&per_page=
     */
    public function globalIndex(Request $request, SuperUserService $superUserService): JsonResponse
    {
        $companyRaw = $request->query('company_id');
        $companyIdInt = ($companyRaw !== null && $companyRaw !== '')
            ? max(1, (int) $companyRaw)
            : null;

        if ($companyIdInt !== null && ! CompanyDetail::query()->where('company_id', $companyIdInt)->exists()) {
            return response()->json(['success' => false, 'message' => 'Company not found.'], 404);
        }

        $search = $request->query('search');
        $status = $request->query('status', 'all');
        if (! in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        $perPage = max(5, min(100, (int) $request->query('per_page', 15)));
        $page = max(1, (int) $request->query('page', 1));

        $paginator = $superUserService->listAllSuperUsers(
            $companyIdInt,
            is_string($search) ? $search : null,
            $status,
            $perPage,
            $page
        );

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

    /**
     * GET /api/companies/{company_id}/super-users
     */
    public function index(Request $request, int $company_id, SuperUserService $superUserService, ExecutiveMappingRepository $executiveMappings): JsonResponse
    {
        abort_unless($executiveMappings->companyExists((int) $company_id), 404);

        $search = $request->query('search');
        $status = $request->query('status', 'all');
        if (! in_array($status, ['all', 'active', 'inactive'], true)) {
            $status = 'all';
        }

        $perPage = max(5, min(100, (int) $request->query('per_page', 15)));
        $page = max(1, (int) $request->query('page', 1));

        $paginator = $superUserService->listForCompany((int) $company_id, is_string($search) ? $search : null, $status, $perPage, $page);

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

    /**
     * GET /api/companies/{company_id}/super-users/{user_id}
     */
    public function show(int $company_id, int $user_id, SuperUserService $superUserService, ExecutiveMappingRepository $executiveMappings): JsonResponse
    {
        abort_unless($executiveMappings->companyExists((int) $company_id), 404);

        $row = $superUserService->findForCompany((int) $company_id, (int) $user_id);
        abort_if($row === null, 404);

        return response()->json([
            'success' => true,
            'data' => $row,
        ], 200);
    }

    /**
     * PUT /api/companies/{company_id}/super-users/{user_id}
     */
    public function update(ExecutiveUpdateRequest $request, int $company_id, int $user_id, SuperUserService $superUserService, ExecutiveMappingRepository $executiveMappings): JsonResponse
    {
        abort_unless($executiveMappings->companyExists((int) $company_id), 404);
        abort_if(
            ! $executiveMappings->executiveMappingExists((int) $company_id, (int) $user_id),
            404
        );

        $validated = $request->validated();

        $removeAvatar = (bool) ($validated['remove_avatar'] ?? false);
        $avatarFile = $request->file('avatar');

        unset($validated['avatar'], $validated['remove_avatar']);

        $servicePayload = [];
        foreach (['first_name', 'last_name', 'email', 'gender', 'date_of_birth', 'marital_status', 'permissions'] as $key) {
            if (array_key_exists($key, $validated)) {
                $servicePayload[$key] = $validated[$key];
            }
        }
        if (array_key_exists('is_active', $validated)) {
            $servicePayload['status'] = (bool) $validated['is_active'];
        }

        if ($servicePayload === [] && ! $removeAvatar && ! $avatarFile) {
            return response()->json([
                'success' => false,
                'message' => 'Provide at least one field to update, avatar, or remove_avatar.',
            ], 422);
        }

        if ($servicePayload !== []) {
            try {
                $superUserService->updateForCompany((int) $company_id, (int) $user_id, $servicePayload);
            } catch (ValidationException $e) {
                $first = collect($e->errors())->flatten()->first();

                return response()->json([
                    'success' => false,
                    'message' => $first ?: 'Validation failed.',
                    'errors' => $e->errors(),
                ], 422);
            }
        }

        $storage = new SuperUserAvatarStorageService();
        if ($removeAvatar) {
            $storage->delete((int) $user_id);
        }
        if ($avatarFile) {
            try {
                $storage->store((int) $user_id, $avatarFile);
            } catch (\Throwable $e) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Executive (Super User) updated successfully.',
        ], 200);
    }

    /**
     * DELETE /api/companies/{company_id}/super-users/{user_id} — deactivates mapping (soft).
     */
    public function destroy(int $company_id, int $user_id, SuperUserService $superUserService, ExecutiveMappingRepository $executiveMappings): JsonResponse
    {
        abort_unless($executiveMappings->companyExists((int) $company_id), 404);

        abort_if(
            ! $executiveMappings->executiveMappingExists((int) $company_id, (int) $user_id),
            404
        );

        (new SuperUserAvatarStorageService())->delete((int) $user_id);
        $superUserService->removeFromCompany((int) $company_id, (int) $user_id);

        return response()->json([
            'success' => true,
            'message' => 'Executive deactivated for this company.',
        ], 200);
    }

    /**
     * POST /api/companies/{company_id}/super-user
     */
    public function store(ExecutiveStoreRequest $request, int $company_id, SuperUserService $superUserService, ExecutiveMappingRepository $executiveMappings): JsonResponse
    {
        abort_unless($executiveMappings->companyExists((int) $company_id), 404);

        $validated = $request->validated();
        $avatarFile = $request->file('avatar');

        try {
            $result = $superUserService->createSuperUser($company_id, $validated);

            $message = $result['email_sent']
                ? 'Executive (Super User) saved. Credentials emailed.'
                : 'Executive linked.';

            if (! empty($result['user_id']) && $avatarFile) {
                try {
                    (new SuperUserAvatarStorageService())->store((int) $result['user_id'], $avatarFile);
                } catch (\Throwable $e) {
                    $message .= ' Photo could not be saved: '.$e->getMessage();
                }
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => ['user_id' => $result['user_id'] ?? null],
            ], 200);
        } catch (ExecutiveConflictNeedsConfirmation $e) {
            return response()->json([
                'success' => false,
                'conflict' => $e->conflictCode,
                'message' => $e->getMessage(),
                'data' => $e->context,
            ], 422);
        } catch (ValidationException $e) {
            $first = collect($e->errors())->flatten()->first();

            return response()->json([
                'success' => false,
                'message' => $first ?: 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
