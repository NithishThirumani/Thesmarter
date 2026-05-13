<?php

namespace App\Services\Due;

use App\Repositories\Contracts\DueRepositoryInterface;
use Carbon\Carbon;

class DueService
{
    protected $dueRepository;

    public function __construct(DueRepositoryInterface $dueRepository)
    {
        $this->dueRepository = $dueRepository;
    }
    public function processOrderDue($orderId, $customerId, $companyId, $totalAmount, $executiveId)
    {
        $currentTime = Carbon::now();

        // Check if due exists for customer
        $existingDue = $this->dueRepository->getDueByCustomer($customerId);

        if (!$existingDue) {
            // Create new due
            $dueId = $this->createNewDue($customerId, $companyId, $totalAmount, $currentTime);
        } else {
            // Update existing due
            $dueId = $existingDue->due_id;
            $this->updateExistingDue($existingDue, $totalAmount, $currentTime);
        }
        // Create order due record
        $this->createOrderDueRecord($orderId, $dueId, $totalAmount, $executiveId, $currentTime);
    }
    protected function createNewDue($customerId, $companyId, $totalAmount, $currentTime)
    {
        $dueData = [
            'customer_id' => $customerId,
            'company_id' => $companyId,
            'due_amount' => $totalAmount,
            'due_status' => 'CD',
            'created_dtm' => $currentTime
        ];
        return $this->dueRepository->createDue($dueData);
    }

    protected function updateExistingDue($existingDue, $totalAmount, $currentTime)
    {
        $dueAmount = $existingDue->due_amount;
        $dueStatus = $existingDue->due_status;
        $totalDueAmount = $dueAmount + $totalAmount;

        if ($dueStatus == "SD") {
            $dueStatus = "CD";
        }

        if ($dueStatus == "BD") {
            if ($dueAmount > $totalAmount) {
                $totalDueAmount = $dueAmount - $totalAmount;
                $dueStatus = "PD";
            } elseif ($dueAmount < $totalAmount) {
                $totalDueAmount = $totalAmount - $dueAmount;
            } elseif ($dueAmount == $totalAmount) {
                $dueStatus = "SD";
            }
        }

        $updateData = [
            'due_amount' => $totalDueAmount,
            'due_status' => $dueStatus,
            'updated_dtm' => $currentTime
        ];

        return $this->dueRepository->updateDue($existingDue->due_id, $updateData);
    }

    protected function createOrderDueRecord($orderId, $dueId, $totalAmount, $executiveId, $currentTime)
    {
        $orderDueData = [
            'order_id' => $orderId,
            'due_id' => $dueId,
            'order_due_amount' => $totalAmount,
            'order_due_cleared' => 0.00,
            'executive_id' => $executiveId,
            'order_due_status' => 'CD',
            'created_dtm' => $currentTime
        ];

        return $this->dueRepository->createOrderDue($orderDueData);
    }
}
