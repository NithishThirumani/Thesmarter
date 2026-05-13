<?php

namespace App\Services\Proforma;

use Exception;
use Illuminate\Support\Facades\DB;
use App\Repositories\ProformaRepository;
use App\Services\Proforma\CalculateSummaryService;
use App\Services\Proforma\ListProformaService;
use App\Services\Proforma\ProformaDetailService;
use App\Services\Proforma\GenerateProformaService;

class ProformaService
{
    protected $proformaRepository;
    protected $calculateSummaryService;
    protected $listProformaService;
    protected $proformaDetailService;
    protected $generateProformaService;

    public function __construct(
        ProformaRepository $proformaRepository,
        CalculateSummaryService $calculateSummaryService,
        ListProformaService $listProformaService,
        ProformaDetailService $proformaDetailService,
        GenerateProformaService $generateProformaService
    ) {
        $this->proformaRepository = $proformaRepository;
        $this->calculateSummaryService = $calculateSummaryService;
        $this->listProformaService = $listProformaService;
        $this->proformaDetailService = $proformaDetailService;
        $this->generateProformaService = $generateProformaService;
    }

    /**
     * Calculate a proforma summary.
     */
    public function calculateProformaSummary(array $data)
    {
        return $this->calculateSummaryService->calculateSummary($data);
    }

    /**
     * Generate a new proforma.
     */
    public function generateProforma(array $data)
    {
        // echo json_encode($this->calculateProformaSummary($data));exit;
        return $this->generateProformaService->createProforma($data,$this->calculateProformaSummary($data));
    }

    /**
     * Export proforma to PDF and email.
     */
    public function exportProforma(string $proformaNo)
    {
        $proforma = $this->proformaDetailService->getDetailedProforma($proformaNo);
        return $proforma;
        $pdf = \PDF::loadView('pdf.proforma', compact('proforma'));

        \Mail::send([], [], function ($message) use ($email, $pdf) {
            $message->to($email)
                    ->subject("Proforma Invoice")
                    ->attachData($pdf->output(), "proforma_invoice.pdf");
        });

        return true;
    }

    /**
     * List all proformas.
     */
    public function listProformas(array $filters)
    {
        return $this->listProformaService->getProformaList($filters);
    }

    /**
     * Get details of a specific proforma.
     */
    public function getProformaDetails(string $proformaNo)
    {
        return $this->proformaDetailService->getDetailedProforma($proformaNo);
    }

    /**
     * Convert a proforma to an order.
     */
    public function convertProformaToOrder(int $proformaId): bool
    {
        DB::beginTransaction();

        try {
            $proforma = $this->proformaRepository->find($proformaId);

            if ($proforma->proforma_status !== 'approved') {
                throw new Exception("Proforma status is not approved; cannot convert to order.");
            }

            $proforma->proforma_status = 'converted';
            $proforma->save();

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Error converting proforma to order: " . $e->getMessage());
        }
    }
}
