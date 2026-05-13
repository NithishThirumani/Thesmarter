<?php

namespace App\Repositories\Contracts;

use App\DuesDetail;

interface DueRepositoryInterface
{
    public function getDueByCustomer(int $customerId): ?DuesDetail;
    public function createDue(array $data);
    public function updateDue(int $dueId, array $data);
    public function createOrderDue(array $orderDueData);
}
