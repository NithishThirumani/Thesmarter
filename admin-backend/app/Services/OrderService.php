<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use App\Repositories\OrderRepository;
use App\Services\Proforma\CalculateSummaryService;
use App\Services\Proforma\ListProformaService;
use App\Services\Proforma\ProformaDetailService;
use App\Services\Proforma\GenerateProformaService;

class OrderService
{
    protected $orderRepository;
    protected $calculateSummaryService;
    protected $listOrderService;
    protected $orderDetailService;
    protected $generateOrderService;
    protected $cartService;

    public function __construct(
        OrderRepository $orderRepository,
        CartService $cartService,
        CalculateSummaryService $calculateSummaryService,
        ListOrderService $listOrderService,
        OrderDetailService $orderDetailService,
        GenerateOrderService $generateOrderService
    ) {
        $this->orderRepository = $orderRepository;
        $this->cartService = $cartService;
        $this->calculateSummaryService = $calculateSummaryService;
        $this->listOrderService = $listOrderService;
        $this->orderDetailService = $orderDetailService;
        $this->generateOrderService = $generateOrderService;
    }

    /**
     * Calculate a proforma summary.
     */
    public function calculateOrderSummary(array $data)
    {
        $cart = $this->cartService->detail($data);
        $cart['discount'] = $data['discount'] ?? [];
        $cart['charge'] = $data['charge'] ?? [];
        return $this->calculateSummaryService->calculateProformaSummary($cart);
    }

    /**
     * Generate a new proforma.
     */
    public function generateProforma(array $data)
    {
        // echo json_encode($this->calculateProformaSummary($data));exit;
        return $this->generateProformaService->createProforma($data, $this->calculateProformaSummary($data));
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
