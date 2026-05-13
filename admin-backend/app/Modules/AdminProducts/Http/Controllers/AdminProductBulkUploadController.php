<?php

namespace App\Modules\AdminProducts\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessProductBulkUploadJob;
use App\Modules\AdminProducts\Services\ProductBulkUploadService;
use App\Services\AdminNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminProductBulkUploadController extends Controller
{
    /** POST /admin/products/bulk-upload */
    public function store(Request $request, ProductBulkUploadService $service): JsonResponse
    {
        $validated = $request->validate([
            'file' => [
                'required',
                'file',
                function (string $attribute, $value, \Closure $fail): void {
                    if (! $value instanceof UploadedFile) {
                        $fail('Invalid upload.');

                        return;
                    }
                    if (strtolower($value->getClientOriginalExtension()) !== 'xlsx') {
                        $fail('The file must be an .xlsx spreadsheet.');

                        return;
                    }
                    $mime = (string) $value->getMimeType();
                    $allowedMimes = [
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        'application/vnd.ms-excel.sheet.macroEnabled.12',
                        'application/zip',
                        'application/x-zip-compressed',
                        'application/octet-stream',
                    ];
                    if (! in_array($mime, $allowedMimes, true)) {
                        $fail('The file must be a valid Excel (.xlsx) workbook.');

                        return;
                    }
                    $realPath = $value->getRealPath();
                    if ($realPath === false || $realPath === '') {
                        $fail('Could not read the uploaded file.');

                        return;
                    }
                    if (! self::pathLooksLikeOfficeOpenXmlSpreadsheet($realPath)) {
                        $fail('The file is not a valid Excel .xlsx workbook.');
                    }
                },
            ],
            'company_id' => 'required|integer|min:1',
            'catalogue_id' => 'required|integer|min:1',
            'dry_run' => 'sometimes|boolean',
            'acting_user_id' => 'sometimes|integer|min:0',
        ]);

        $companyId = (int) $validated['company_id'];
        $catalogueId = (int) $validated['catalogue_id'];
        $dryRun = (bool) ($validated['dry_run'] ?? false);
        $actingUserId = isset($validated['acting_user_id']) ? (int) $validated['acting_user_id'] : 0;

        $relativePath = $request->file('file')->store('bulk-products', 'local');
        $absolutePath = Storage::disk('local')->path($relativePath);

        $deferQueued = false;

        try {
            $result = $service->execute($absolutePath, $companyId, $catalogueId, $actingUserId, $dryRun, false);

            if (($result['success'] ?? false) === false) {
                return response()->json(['success' => false, 'data' => $result], 422);
            }

            if (($result['defer'] ?? false) === true && ! $dryRun) {
                $deferQueued = true;
                $token = (string) Str::uuid();

                Cache::put('bulkupload:'.$token, [
                    'status' => 'PROCESSING',
                    'total_rows' => $result['total_rows'] ?? 0,
                ], now()->addMinutes(90));

                ProcessProductBulkUploadJob::dispatch($token, $relativePath, $companyId, $catalogueId, $actingUserId);

                return response()->json([
                    'success' => true,
                    'data' => [
                        'status' => 'QUEUED',
                        'result_token' => $token,
                        'total_rows' => $result['total_rows'] ?? 0,
                        'message' => $result['message'] ?? 'Import queued.',
                    ],
                ], 202);
            }

            if (($result['status'] ?? '') === 'COMPLETED') {
                $inserted = (int) ($result['inserted'] ?? 0);
                AdminNotificationService::record(
                    $inserted > 0 ? 'success' : 'warning',
                    'Bulk product import finished',
                    sprintf(
                        'Company #%d, catalogue #%d: %d product(s) inserted; %d persist failure(s).',
                        $companyId,
                        $catalogueId,
                        $inserted,
                        (int) ($result['failed'] ?? 0)
                    )
                );
            }

            return response()->json([
                'success' => true,
                'data' => $result,
            ]);
        } finally {
            if (! $deferQueued) {
                Storage::disk('local')->delete($relativePath);
            }
        }
    }

    /** GET /admin/products/bulk-upload/result?token= */
    public function result(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'token' => 'required|string|max:72',
        ]);
        $key = 'bulkupload:'.$validated['token'];
        $payload = Cache::get($key);

        if ($payload === null) {
            return response()->json([
                'success' => false,
                'message' => 'Unknown or expired result token.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    /**
     * PHP fileinfo often labels .xlsx as application/zip; confirm OOXML spreadsheet layout.
     */
    private static function pathLooksLikeOfficeOpenXmlSpreadsheet(string $path): bool
    {
        if (! class_exists(\ZipArchive::class)) {
            return true;
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return false;
        }
        $hasWorkbook = $zip->locateName('xl/workbook.xml') !== false;
        $zip->close();

        return $hasWorkbook;
    }
}
