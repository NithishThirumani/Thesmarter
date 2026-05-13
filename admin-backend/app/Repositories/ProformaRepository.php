<?php

namespace App\Repositories;

use App\ProformaDetails;
use App\ProformaItemDetail;
use App\ProformaDiscount;
use App\ProformaProductDiscounts;
use App\ProformaProductTaxes;
use App\ProformaSequence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProformaRepository
{
    /**
     * Get list of proformas with optional filters and pagination.
     *
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateProformasOld(array $filters = [])
    {
        return Proforma::query()
            ->when(isset($filters['status']), fn($query) => $query->where('proforma_status', $filters['status']))
            ->when(isset($filters['date_from']), fn($query) => $query->whereDate('proforma_date_time', '>=', $filters['date_from']))
            ->when(isset($filters['date_to']), fn($query) => $query->whereDate('proforma_date_time', '<=', $filters['date_to']))
            ->paginate(10);
    }
    public function detailProforma(?int $proformaId = null, ?string $proformaNo = null): ProformaDetails
    {
        $query = ProformaDetails::with([
            'company',
            'items.product',
            'customer.login',
            'executive.login',
            'branch.contact'
        ]);
        // Apply the appropriate condition based on the available parameter
        if (!is_null($proformaId)) {
            $query->where('id', $proformaId);
        } elseif (!is_null($proformaNo)) {
            $query->where('proforma_no', $proformaNo);
        } else {
            //  return null; // Return null or handle the case where neither parameter is provided
        }
        $proforma = $query->first();

        if (!$proforma) {
            // return null; // Handle case where no proforma is found
        }
        $proforma->items->each(function ($item) {
            $item->load(['tax' => function ($query) use ($item) {
                $query->where('product_id', $item->product_id);
            }]);
            // Load discounts for the item
            $item->load(['discount' => function ($query) use ($item) {
                $query->where('product_id', $item->product_id); // Adjust this condition if needed
            }]);
        });
        // Load additional relationships with filtering
        $proforma->load([
            'customer.defaultContactUser.contactDetails'
        ]);
        $proforma->load('customerAdditionalDetail');
        return $proforma;
    }
    public function paginateProformas(array $filters)
    {
        $query = ProformaDetails::with(['customer.login', 'executive.login', 'branch.contact'])
            ->withCount('items')
            ->where('company_id', $filters['company_id'])
            ->when(isset($filters['executive_id']), function ($query) use ($filters) {
                $query->where('user_id', $filters['loggedin_user_id']);
            })
            ->when(isset($filters['customer_id']), function ($query) use ($filters) {
                $query->where('customer_id', $filters['customer_id']);
            })
            ->when(isset($filters['from_date']), function ($query) use ($filters) {
                $query->whereDate('proforma_date_time', '>=', $filters['from_date']);
            })
            ->when(isset($filters['to_date']), function ($query) use ($filters) {
                $query->whereDate('proforma_date_time', '<=', $filters['to_date']);
            })
            ->when(isset($filters['proforma_status']), function ($query) use ($filters) {
                $query->whereIn('status', $filters['proforma_status']);
            })
            ->when(isset($filters['branch_id']), function ($query) use ($filters) {
                $query->whereIn('branch_id', $filters['branch_id']);
            })
            ->when(isset($filters['customer_name']), function ($query) use ($filters) {
                $query->whereHas('customer', function ($q) use ($filters) {
                    $q->where('first_name', 'like', '%' . $filters['customer_name'] . '%')
                        ->orWhere('last_name', 'like', '%' . $filters['customer_name'] . '%');
                });
            })
            ->when(isset($filters['customer_phone']), function ($query) use ($filters) {
                $query->whereHas('customePhone', function ($q) use ($filters) {
                    $q->where('user_mobile', $filters['customer_phone']);
                });
            })
            ->when(isset($filters['proforma_no']), function ($query) use ($filters) {
                $query->where('proforma_no', $filters['proforma_no']);
            })
            ->orderBy('id', 'desc'); // Ensure results are sorted in descending order by ID

        return [
            'proformas' => $query->paginate($filters['limit'] ?? 10, ['*'], 'page', ($filters['offset'] ?? 0) / ($filters['limit'] ?? 10) + 1),
            'total_count' => $query->count()
        ];
    }

    /**
     * Get detailed information of a proforma by ID, including items, discounts, and taxes.
     *
     * @param int $proformaId
     * @return Proforma
     */
    // public function findDetailedProforma(int $proformaId): Proforma
    // {
    //     return Proforma:F

    /**
     * Create a new proforma with basic information.
     *
     * @param array $data
     * @return Proforma
     */
    public function createProforma(array $data): ProformaDetails
    {
        $currentDateTime = Carbon::now()->toDateTimeString();
        return ProformaDetails::create([
            'proforma_no' => $this->generateProformaId($data['company_id']),
            'company_id' => $data['company_id'],
            'branch_id' => $data['branch_id'],
            'proforma_date_time' => $currentDateTime,
            'customer_id' => $data['customer_id'],
            'executive_id' => $data['executive_id'],
            'net_amount' => $data['net_amount'],
            'discount_amount' => $data['discount_amount'],
            'tax_amount' => $data['tax_amount'],
            'charge_amount' => $data['charge_amount'],
            'total_amount' => $data['total_amount'],
            // 'discount_id' => $data['discount_id'],
            // 'charge_id' => $data['charge_id'],
            'proforma_status' => 'C'
        ]);
    }

    /**
     * Add a product item to a proforma.
     *
     * @param int $proformaId
     * @param array $itemData
     * @return ProformaItemDetail
     */
    public function addProductToProforma(int $proformaId, array $itemData): ProformaItemDetail
    {
        return ProformaItemDetail::create([
            'proforma_id' => $proformaId,
            'product_id' => $itemData['product_id'],
            'mpp_id' => $itemData['detail']['price']['mpp_id'],
            'discount_id' => $itemData['detail']['discount']['discount_id'] ?? null,
            'unit_price' => $itemData['detail']['price']['unit_price'],
            'base_amount' => $itemData['detail']['price']['base_amount'],
            'product_quantity' => $itemData['quantity'],
            'net_amount' => $itemData['net_amount'],
            'discount_amount' => $itemData['discount_amount'],
            'tax_amount' => $itemData['tax_amount'],
            'charge_amount' => $itemData['charge_amount'],
            'total_amount' => $itemData['total_amount'],
            'is_dynamically_priced' => $itemData['is_dynamically_priced'] ?? false,
        ]);
    }

    /**
     * Add order-level discount to a proforma.
     *
     * @param int $proformaId
     * @param array $discountData
     * @return ProformaDiscount
     */
    public function addOrderLevelDiscount(int $proformaId, array $discountData): ProformaDiscount
    {
        return ProformaDiscount::create([
            'proforma_id' => $proformaId,
            'dd_id' => $discountData['dd_id'],
            'value' => $discountData['value'],
            'amount' => $discountData['amount'],
        ]);
    }

    /**
     * Add product-level discount to a proforma.
     *
     * @param int $proformaId
     * @param array $discountData
     * @return ProformaProductDiscount
     */
    public function addProductDiscount(int $proformaId, array $item)
    {

        if ((empty($item['discount']))) {
            return false;
        }
        if ($item['discount']['value'] == 0) {
            return false;
        }
        return ProformaProductDiscounts::create([
            'proforma_id' => $proformaId,
            'product_id' => $item['product_id'],
            'discount_detail_id' => $item['discount']['dd_id'],
            'amount' => $item['discount']['amount'],
            'value' => $item['discount']['value']
        ]);
    }

    /**
     * Add taxes to a product in a proforma.
     *
     * @param int $proformaId
     * @param array $taxData
     * @return ProformaProductTaxes
     */
    public function addProductTax(int $proformaId, array $item): void
    {

        if (is_null($item['taxes'])) {
            return;
        }
        foreach ($item['taxes'] as $component => $tax) {
            ProformaProductTaxes::create([
                'proforma_id' => $proformaId,
                'product_id' => $item['product_id'],
                'td_id' => $component,
                'value' => $tax['value'],
                'amount' => $tax['amount'],
                'name' => $tax['name']
            ]);
        }
    }

    /**
     * Get products with taxes and discounts for calculating the summary.
     *
     * @param int $productId
     * @return Collection
     */
    public function getProductsWithTaxesAndDiscounts(int $productId)
    {
        return ProformaItemDetails::with(['taxes', 'discounts'])
            ->where('product_id', $productId)
            ->get();
    }

    public function addProductCharge(int $productId, array $item)
    {
        return false;   // Need to implement this 
    }
    private function generateProformaId($companyId)
    {
        $currentYear = Carbon::now()->year;
        $prefix = 'PF';

        return DB::transaction(function () use ($companyId, $currentYear, $prefix) {
            // Lock the table to prevent race conditions
            DB::table('proforma_sequences')->lockForUpdate()->get();

            // Get the existing sequence or start from zero
            $sequence = DB::table('proforma_sequences')
                ->where('company_id', $companyId)
                ->where('year', $currentYear)
                ->first();

            if ($sequence) {
                // Increment the sequence number
                $newSequenceNumber = $sequence->sequence_number + 1;
                DB::table('proforma_sequences')
                    ->where('company_id', $companyId)
                    ->where('year', $currentYear)
                    ->update(['sequence_number' => $newSequenceNumber]);
            } else {
                // Insert new record with sequence 1
                $newSequenceNumber = 1;
                DB::table('proforma_sequences')->insert([
                    'company_id' => $companyId,
                    'year' => $currentYear,
                    'sequence_number' => $newSequenceNumber
                ]);
            }

            // Generate the Proforma ID
            return sprintf('%s%s-%d%03d', $prefix, $currentYear, $companyId, $newSequenceNumber);
        });
    }
}
