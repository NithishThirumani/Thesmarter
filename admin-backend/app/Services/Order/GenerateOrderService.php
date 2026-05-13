<?php

namespace App\Services\Order;

use App\Repositories\ContactRepository;
use App\Repositories\OrderRepository;
use App\Services\ChargeService;
use App\Services\Discount\DiscountService;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Throwable;

class GenerateOrderService
{
    protected $orderRepository;
    protected $paymentService;
    protected $discountService;
    protected $chargeService;
    protected $contactRepository;
    protected $orderId;
    protected $allowedLobIds;
    protected $currentDataTime;

    public function __construct(
        OrderRepository $orderRepository,
        PaymentService $paymentService,
        DiscountService $discountService,
        ChargeService $chargeService,
        ContactRepository $contactRepository
    ) {
        $this->orderRepository = $orderRepository;
        $this->paymentService = $paymentService;
        $this->discountService = $discountService;
        $this->chargeService = $chargeService;
        $this->contactRepository = $contactRepository;
        $this->allowedLobIds = array(4);
        $this->currentDataTime = Carbon::now()->format('Y-m-d H:i:s');
    }
    public function createOrder($data, $summary)
    {
        // try {
        return DB::transaction(function () use ($data, $summary) {
            // Process core order confirmation
            $order = $this->orderRepository->confirmOrder($summary);
            $this->orderId = $order['order_id'];

            foreach ($summary['items'] as $item) {
                // Process order item 
                $this->orderRepository->updateOrderItems($this->orderId, $item);

                $this->processProductTaxes($item['product_id'], (array)$item['taxes']);
                $this->processProductDiscount($item);
                $this->processProductCharge($item['product_id'], $item['charge']);
                $this->updateEntityStatus(
                    $item['product_id'],
                    $data['lob_id'] ?? null,
                    $data['appointment_id'] ?? null
                );
            }

            // Process payments
            $this->paymentService->processPayment($this->orderId, $order['total_amount'], $data);

            // Handle discounts
            $this->discountService->processDiscount($this->orderId, $summary['discount']);

            // Handle charges
            $this->chargeService->processOrderCharge($this->orderId, $summary['charges']);

            // Add Shipping details 
            $this->processOrderShipping($data['shipping']);

            // Release entity 
            $this->relaseOrderEntity($data['appointment_id'], $data['lob_id']);
            // Trigger Order Confirmed 

            // Trigger Kitchen Notification

            return $order;
        });
        // } catch (Throwable $e) {
        //     // Log or report the error
        //     \Log::error('Order creation failed', [
        //         'error' => $e->getMessage(),
        //         'trace' => $e->getTraceAsString(),
        //     ]);

        //     // Optionally throw a custom exception or return response
        //     throw new \Exception("Order creation failed: " . $e->getMessage());
        // }
    }
    private function processOrderShipping(array $shipping): void
    {
        if ((!is_null($shipping)) && (!empty($shipping))) {
            if (!is_null($shipping['contact_id'])) {
                $shipping = $this->contactRepository->getContactDetailById($shipping['contact_id']);
                $shipping = $shipping->toArray();
            }

            $this->orderRepository->addOrderShipping(
                $this->orderId,
                $shipping
            );
        }
    }
    private function processProductTaxes(int $productId, array $taxes): void
    {

        if ((!is_null($taxes) && !empty($taxes))) {
            foreach ($taxes as $tax_detail_id => $detail) {
                $this->orderRepository->addProductTaxes($this->orderId, $productId, $tax_detail_id, $detail);
            }
        }
    }
    private function processProductDiscount(array $item): void
    {
        if ((!is_null($item['discount'])) && (!empty($item['discount']) && ($item['discount']['value'] > 0))) {
            $this->orderRepository->addProductDiscount($this->orderId, $item['product_id'], $item['discount']);
        }
    }
    private function processProductCharge(int $productId, array $charges): void
    {
        if ((!is_null($charges)) && (!empty($charges))) {
            $this->orderRepository->addProductCharge($this->orderId, $productId, $charges);
        }
    }
    private function updateEntityStatus(int $productId, ?int $lobId, ?int $appointmentId): void
    {
        if (in_array($lobId, $this->allowedLobIds) && empty($appointmentId)) {
            // Order Entty status is changed
            $this->orderRepository->changeOrderEntityStatus(
                $this->orderId,
                $productId,
                'WAIT'
            );
            // Trigger Notification to kitchen
        }
    }
    private function relaseOrderEntity(?int $appointmentId, int $lobId): void
    {
        if ((!is_null($appointmentId)) && (in_array($lobId, $this->allowedLobIds))) {

            $this->orderRepository->releaseEntity(
                $appointmentId,
                5,
                $this->currentDataTime
            );
        }
    }
}
