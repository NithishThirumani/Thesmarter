<?php

namespace App\Services\Order;

use Exception;
use Illuminate\Support\Facades\DB;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\Cart\CartService;
use App\Services\Proforma\CalculateSummaryService;
use App\Services\Order\ListOrderService;
use App\Services\Order\OrderDetailService;
use App\Services\Order\GenerateOrderService;

class OrderService
{
    protected $orderRepository;
    protected $calculateSummaryService;
    protected $listOrderService;
    protected $orderDetailService;
    protected $generateOrderService;
    protected $cartService;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
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
       
        $cart = $this->cartService->getUserCart($data);
        if (is_null($cart)||empty($cart))
        {
            return [];
        }
        $cart['discount'] = $data['discount'] ?? [];
        $cart['charges'] = $data['charges'] ?? [];
        return $this->calculateSummaryService->calculateSummary($cart);
    }

    /**
     * Generate a new proforma.
     */
    public function confirmOrder(array $data)
    {
        
        return $this->generateOrderService->createOrder($data,$this->calculateOrderSummary($data));
        // echo json_encode($this->calculateProformaSummary($data));exit;
        // return $this->generateProformaService->createProforma($data, $this->calculateProformaSummary($data));
    }

    /**
     * Export proforma to PDF and email.
     */
    public function exportOrder(string $orderId)
    {
        $order = $this->orderDetailService->execute($orderId);
        return $order;  
       /* $pdf = \PDF::loadView('pdf.proforma', compact('proforma'));

        \Mail::send([], [], function ($message) use ($email, $pdf) {
            $message->to($email)
                ->subject("Proforma Invoice")
                ->attachData($pdf->output(), "proforma_invoice.pdf");
        });*/

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
    public function getOrderDetails(string $orderNo)
    {
        return $this->orderDetailService->execute($orderNo);
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
