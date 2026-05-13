<?php

namespace App\Modules\AdminPaymentMethod\Services;

use App\CompanyPayment;
use App\OrderPayments;
use App\PaymentMethods;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class PaymentMethodAdminService
{
    public function list(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $q = PaymentMethods::query();

        if (!empty($filters['search'])) {
            $raw = trim((string) $filters['search']);
            $s = '%' . $raw . '%';
            $q->where(function ($sub) use ($s) {
                $sub->where('payment_name', 'like', $s)
                    ->orWhere('payment_description', 'like', $s)
                    ->orWhere('payment_type', 'like', $s);
            });
        }

        if (!empty($filters['payment_status']) && $filters['payment_status'] !== 'all') {
            if ($filters['payment_status'] === 'active') {
                $q->where('active_status', 1);
            } elseif ($filters['payment_status'] === 'inactive') {
                $q->where(function ($w) {
                    $w->where('active_status', 0)->orWhereNull('active_status');
                });
            }
        }

        return $q->orderBy('payment_name')->paginate($perPage);
    }

    public function getOne(int $paymentId): PaymentMethods
    {
        return PaymentMethods::findOrFail($paymentId);
    }

    public function create(array $data): PaymentMethods
    {
        return PaymentMethods::create([
            'payment_name' => $data['payment_name'],
            'payment_description' => $data['payment_description'] ?? null,
            'active_status' => $this->normalizeActive($data['active_status'] ?? 1),
            'payment_type' => $data['payment_type'],
        ]);
    }

    /**
     * @throws ModelNotFoundException
     */
    public function update(int $paymentId, array $data): PaymentMethods
    {
        $method = PaymentMethods::findOrFail($paymentId);
        if (array_key_exists('active_status', $data)) {
            $data['active_status'] = $this->normalizeActive($data['active_status']);
        }
        $method->update($data);

        return $method->fresh();
    }

    /**
     * Block delete when referenced by companies or orders (avoid cascade surprises).
     *
     * @throws ModelNotFoundException
     * @throws RuntimeException
     */
    public function delete(int $paymentId): void
    {
        $method = PaymentMethods::findOrFail($paymentId);

        $companyFk = $this->companyPaymentsPaymentFkColumn();
        $inCompany = $companyFk !== null
            && CompanyPayment::query()->where($companyFk, $paymentId)->exists();
        $inOrders = OrderPayments::where('payment_mode_id', $paymentId)->exists();

        if ($inCompany || $inOrders) {
            throw new RuntimeException(
                'This payment method is in use by companies or orders and cannot be deleted.'
            );
        }

        $method->delete();
    }

    /**
     * @param mixed $v
     */
    private function normalizeActive($v): int
    {
        if ($v === 1 || $v === '1' || $v === true) {
            return 1;
        }

        return 0;
    }

    /**
     * Legacy DBs may use `payment_id` instead of `payment_method_id` on company_payments.
     *
     * @return non-empty-string|null
     */
    private function companyPaymentsPaymentFkColumn(): ?string
    {
        if (! Schema::hasTable('company_payments')) {
            return null;
        }
        if (Schema::hasColumn('company_payments', 'payment_method_id')) {
            return 'payment_method_id';
        }
        if (Schema::hasColumn('company_payments', 'payment_id')) {
            return 'payment_id';
        }

        return null;
    }
}
