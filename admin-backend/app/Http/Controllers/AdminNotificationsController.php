<?php

namespace App\Http\Controllers;

use App\Models\AdminNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AdminNotificationsController extends Controller
{
    /**
     * GET /api/admin/notifications?page=&per_page=
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
            'page' => 'sometimes|integer|min:1',
        ]);

        $perPage = (int) $request->input('per_page', 30);

        try {
            if (! Schema::hasTable('admin_notifications')) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'meta' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => $perPage,
                        'total' => 0,
                    ],
                ]);
            }
        } catch (Throwable $e) {
            return response()->json([
                'success' => true,
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                ],
            ]);
        }

        $page = AdminNotification::query()
            ->orderByDesc('id')
            ->paginate(min(max($perPage, 1), 100));

        $items = collect($page->items())->map(static function (AdminNotification $row) {
            return [
                'id' => (int) $row->id,
                'kind' => (string) $row->kind,
                'title' => (string) $row->title,
                'body' => (string) $row->body,
                'created_at' => $row->created_at?->toIso8601String(),
            ];
        })->values()->all();

        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $page->currentPage(),
                'last_page' => $page->lastPage(),
                'per_page' => $page->perPage(),
                'total' => $page->total(),
            ],
        ]);
    }
}
