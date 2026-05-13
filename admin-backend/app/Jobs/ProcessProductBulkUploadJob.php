<?php

namespace App\Jobs;

use App\Modules\AdminProducts\Services\ProductBulkUploadService;
use App\Services\AdminNotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Throwable;

final class ProcessProductBulkUploadJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** @var string */
    public $token;

    /** @var string */
    public $relativePath;

    /** @var int */
    public $companyId;

    /** @var int */
    public $catalogueId;

    /** @var int */
    public $actingUserId;

    public function __construct(
        string $token,
        string $relativePath,
        int $companyId,
        int $catalogueId,
        int $actingUserId
    ) {
        $this->token = $token;
        $this->relativePath = $relativePath;
        $this->companyId = $companyId;
        $this->catalogueId = $catalogueId;
        $this->actingUserId = $actingUserId;
    }

    public function handle(ProductBulkUploadService $service): void
    {
        $absolutePath = Storage::disk('local')->path($this->relativePath);

        try {
            $result = $service->execute($absolutePath, $this->companyId, $this->catalogueId, $this->actingUserId, false, true);
            Cache::put('bulkupload:'.$this->token, array_merge(['status' => 'COMPLETED'], $result), now()->addMinutes(60));
            if (($result['status'] ?? '') === 'COMPLETED') {
                $inserted = (int) ($result['inserted'] ?? 0);
                AdminNotificationService::record(
                    $inserted > 0 ? 'success' : 'warning',
                    'Bulk product import finished',
                    sprintf(
                        'Company #%d, catalogue #%d: %d product(s) inserted; %d persist failure(s) (async).',
                        $this->companyId,
                        $this->catalogueId,
                        $inserted,
                        (int) ($result['failed'] ?? 0)
                    )
                );
            }
        } catch (Throwable $e) {
            report($e);
            Cache::put('bulkupload:'.$this->token, [
                'success' => false,
                'status' => 'FAILED',
                'message' => $e->getMessage(),
            ], now()->addMinutes(60));
        } finally {
            Storage::disk('local')->delete($this->relativePath);
        }
    }
}
