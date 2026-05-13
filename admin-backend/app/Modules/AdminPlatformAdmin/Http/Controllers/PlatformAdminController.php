<?php

namespace App\Modules\AdminPlatformAdmin\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AdminAuth\Models\AdminUser;
use App\Modules\AdminPlatformAdmin\Services\PlatformAdminService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PlatformAdminController extends Controller
{
    private PlatformAdminService $service;

    public function __construct(PlatformAdminService $service)
    {
        $this->service = $service;
    }

    /**
     * GET /admin/platform-admins
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = min(max($perPage, 1), 100);

        $v = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|string|max:32',
            'sort' => 'nullable|string|max:32',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $v->errors()], 422);
        }

        $list = $this->service->list([
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'sort' => $request->input('sort'),
        ], $perPage);

        $items = array_map(fn (AdminUser $row) => $this->service->rowToArray($row), $list->items());

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $list->currentPage(),
                'last_page' => $list->lastPage(),
                'per_page' => $list->perPage(),
                'total' => $list->total(),
            ],
        ], 200);
    }

    /**
     * POST /admin/platform-admins
     */
    public function store(Request $request): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('admin_users', 'email')],
            'phone_number' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9\-\s\(\)]{7,20}$/'],
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $v->errors()], 422);
        }

        /** @var AdminUser|null $actor */
        $actor = $request->attributes->get('admin_user');
        if (!$actor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $admin = $this->service->create([
                'name' => $request->input('name'),
                'email' => $request->input('email'),
                'phone_number' => $request->input('phone_number'),
            ], $actor);

            return response()->json([
                'success' => true,
                'message' => 'Super admin created and invitation email sent.',
                'data' => $this->service->rowToArray($admin),
            ], 201);
        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * GET /admin/platform-admins/{id}
     */
    public function show(Request $request, string $id): JsonResponse
    {
        try {
            $admin = $this->service->findManaged($id);

            return response()->json([
                'success' => true,
                'data' => $this->service->rowToArray($admin),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Super admin not found.'], 404);
        }
    }

    /**
     * PUT /admin/platform-admins/{id}
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'email' => ['sometimes', 'email', 'max:255', Rule::unique('admin_users', 'email')->ignore($id, 'id')],
            'phone_number' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9\-\s\(\)]{7,20}$/'],
            'is_active' => 'sometimes|boolean',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $v->errors()], 422);
        }

        /** @var AdminUser|null $actor */
        $actor = $request->attributes->get('admin_user');
        if (!$actor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $admin = $this->service->update($id, $request->only(['name', 'email', 'phone_number', 'is_active']), $actor);

            return response()->json([
                'success' => true,
                'message' => 'Updated.',
                'data' => $this->service->rowToArray($admin),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Super admin not found.'], 404);
        }
    }

    /**
     * PATCH /admin/platform-admins/{id}/status — body: is_active boolean
     */
    public function patchStatus(Request $request, string $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'is_active' => 'required|boolean',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $v->errors()], 422);
        }

        /** @var AdminUser|null $actor */
        $actor = $request->attributes->get('admin_user');
        if (!$actor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $admin = $this->service->setActive($id, $request->boolean('is_active'), $actor);

            return response()->json([
                'success' => true,
                'message' => $admin->is_active ? 'Activated.' : 'Deactivated.',
                'data' => $this->service->rowToArray($admin),
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Super admin not found.'], 404);
        }
    }

    /**
     * POST /admin/platform-admins/{id}/reset-pin
     */
    public function resetPin(Request $request, string $id): JsonResponse
    {
        /** @var AdminUser|null $actor */
        $actor = $request->attributes->get('admin_user');
        if (!$actor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $result = $this->service->resetPin($id, $actor);
            /** @var AdminUser $admin */
            $admin = $result['admin'];
            $mailSent = $result['mail_sent'];

            return response()->json([
                'success' => true,
                'message' => $mailSent
                    ? 'PIN reset. New PIN emailed.'
                    : 'PIN reset. Email could not be sent — check mail logs.',
                'data' => $this->service->rowToArray($admin),
                'meta' => ['mail_sent' => $mailSent],
            ], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Super admin not found.'], 404);
        }
    }

    /**
     * DELETE /admin/platform-admins/{id}
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        /** @var AdminUser|null $actor */
        $actor = $request->attributes->get('admin_user');
        if (!$actor) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $this->service->delete($id, $actor);

            return response()->json(['success' => true, 'message' => 'Deleted.'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Super admin not found.'], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Unable to delete.',
                'errors' => $e->errors(),
            ], 422);
        }
    }
}
