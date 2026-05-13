<?php
namespace App\Repositories;

use App\Repositories\Contracts\DueRepositoryInterface;
use App\DuesDetail;
use App\OrderDuesDetail;

class DueRepository implements DueRepositoryInterface
{
    public function getDueByCustomer(int $customerId): ?DuesDetail
    {
        return DuesDetail::where('customer_id', $customerId)->first();
    }
    public function createDue(array $data)
    {
        return DuesDetail::insertGetId($data);
    }
    public function updateDue(int $dueId,array $data)
    {
        return DuesDetail::where('due_id', $dueId)
        ->update($data);
    }
    public function createOrderDue(array $orderDueData)
    {
        return OrderDuesDetail::insert($orderDueData);

    }
}