<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Validator;
use Dompdf\Dompdf;
use Carbon\Carbon;
use App\ContactDetail;
use App\DiscountMaster;
use App\DiscountDetail;
use App\Http\Resources\OrderResource;
use App\Jobs\SendEmail;
use App\OrderDetail;
use App\TaxComponents;
use App\TaxDetail;
use App\TaxMaster;
use App\UserLogin;
use App\MerchantCatalogueProducts;
use App\Services\Proforma\CalculateSummaryService;
use App\Services\Order\OrderService;
use Log;
use Barryvdh\DomPDF\Facade\Pdf;

class OrderController extends Controller
{
    protected $orderNo;
    protected $customerId;
    protected $companyId;
    protected $branchId;
    protected $items;
    protected $orderType = 'Normal';
    protected $orderTaxes = array();
    protected $calculateSummaryService;
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    public function calculateSummary(Request $request)
    {
        try {
            $data = $request->all();

            $summary =  $this->orderService->calculateOrderSummary($data);
            return response()->json([
                'success' => true,
                'message' => 'Calculated Summary',
                'data' => $summary
            ], 200);
        } catch (\Execption $ex) {
            return response()->json([
                'success' => false,
                'message' => 'Error calculating summary: ',
                'error' => $ex->getMessage()
            ], 500);
        }
    }
    public function confirmOrder(Request $request)
    {
        $data = $request->all();

        $this->customerId = $data['customer_id'] == 0 ? 2 : $data['customer_id'];
        $this->companyId = $data['company_id'];
        $this->branchId = $data['branch_id'];
        $this->orderType = $data['order_type'] ?? 'Normal';

        $cart = $this->getCartDetails();
        //   if($this->companyId ==2){
        //     return response()->json([
        //         'success' => false,
        //         'message' => $cart ,
        //         'data' => []
        //     ], 200);
        // }
        $cart = $cart->toArray();
        $cart['discount'] = $data['discount'] ?? [];
        $cart['charges'] = $data['charges'] ?? [];
        $cart['payment'] = $data['payment'] ?? [];
        $cart['shipping'] = $data['shipping'] ?? [];
        $cart['appointment_id'] = $data['appointment_id'] ?? null;
        $cart['lob_id'] = $data['lob_id'];

        $order = $this->orderService->confirmOrder($cart);

        return response()->json([
            'success' => true,
            'message' => 'Order Confirmed',
            'data' => [
                'order_id' => $order['order_id'],
            ]
        ], 200);
    }
    public function show($orderNumber)
    {
        $order = $this->orderService->getOrderDetails($orderNumber);
        
        return response()->json([
            'success' => true,
            'message' => 'Order details',
            'data' => new OrderResource($order)
        ], 200);
        // return response()->json([
        //     'success' => true,
        //     'data' => $order
        // ], Response::HTTP_OK);
    }
    public function export(Request $request, $orderId)
    {

        try {
            $order = $this->orderService->exportOrder($orderId);

            // return view('invoices.order-template', compact('order'));

            // Load the PDF view
            $pdf = Pdf::loadView('invoices.order-template', compact('order'));
            // // Set paper size and orientation
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'dpi' => 120,
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true
            ]);


            $pdfContent = $pdf->output();

            // Set headers explicitly
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="Invoice-' . $orderId . '.pdf"',
                'Content-Length' => strlen($pdfContent),
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error exporting proforma: ' . $e->getMessage()], 500);
        }
    }
    public function preview(Request $request, $orderId)
    {

        try {
            $order = $this->orderService->exportOrder($orderId);

            return view('invoices.printer-preview', compact('order'));

            // // Load the PDF view
            // $pdf = Pdf::loadView('invoices.order-template', compact('order'));
            // // // Set paper size and orientation
            // $pdf->setPaper('a4', 'portrait');
            // $pdf->setOptions([
            //     'dpi' => 120,
            //     'defaultFont' => 'sans-serif',
            //     'isHtml5ParserEnabled' => true,
            //     'isRemoteEnabled' => true
            // ]);


            // $pdfContent = $pdf->output();

            // // Set headers explicitly
            // return response($pdfContent, 200, [
            //     'Content-Type' => 'application/pdf',
            //     'Content-Disposition' => 'attachment; filename="Invoice-' . $orderId . '.pdf"',
            //     'Content-Length' => strlen($pdfContent),
            //     'Cache-Control' => 'no-cache, no-store, must-revalidate',
            //     'Pragma' => 'no-cache',
            //     'Expires' => '0',
            // ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error exporting proforma: ' . $e->getMessage()], 500);
        }
    }
    public function generateOrder(Request $request)
    {

        $validatedData = $request->all();
        $validator = Validator::make($validatedData, [
            'customer_id' => 'required|integer', // Adjust 'customers' and 'id' to match your database table and column names
            'company_id' => 'required|integer',
            'branch_id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }


        $summary =  $this->orderService->calculateOrderSummary($validatedData);
        return response()->json([
            'error' => false,
            'message' => 'Calculated Summary',
            'data' => $summary
        ], 200);
        // Retrieve and set the customer ID

        $this->customerId = $validatedData['customer_id'];
        $this->companyId = $validatedData['company_id'];
        $this->branchId = $validatedData['branch_id'];
        $this->orderType = $validatedData['order_type'] ?? 'Normal';
        if ($this->orderType == 'Proforma') {
            $jsonData = $request->all();
            // Log::info($jsonData);
            $cart = json_decode(json_encode($jsonData));
            $cart->total_amount = 0.00;
            $cart->net_amount  = 0.00;
            $cart->tax_amount = 0.00;
            $cart->charge_amount  = 0.00;
            $cart->taxes  = 0.00;
        } else {
            $cart = $this->getCartDetails();
        }


        foreach ($cart->items as $item) {
            $product = $this->getProductDetails($item->product_id);
            $taxValueSum = 0.00;
            $taxes = array();
            $totalProductTaxAmount = 0.00;

            $sellingPrice = $product->price->unit_price;
            $inclusiveTax = $product->taxInclusive != null ? $product->taxInclusive->inclusive_flag : 'N';
            $unitPrice = $sellingPrice ?? 0.00;
            $netProductAmount = $unitPrice;
            $discountAmount = $this->calculateDiscount($product, $unitPrice);
            $unitPrice -= $discountAmount;
            $netProductAmount -= $discountAmount;
            if (!is_null($product->tax)) {
                $taxValueSum = $this->calculateTaxValueSum($product->tax->tax_id);

                list($taxes, $totalProductTaxAmount) = $this->calculateTaxes($product, $netProductAmount, $taxValueSum, $inclusiveTax, $item);
            }
            $product = $this->updateProductDetails($product, $netProductAmount, $taxes);

            $item = $this->updateItemDetails($item, $netProductAmount, $totalProductTaxAmount, $product, $taxes);

            $this->accumulateTotals($cart, $item);
        }

        return $this->generateResponse($cart);
    }

    private function getCartDetails()
    {
        return OrderDetail::with(['items:order_id,product_id,product_quantity as quantity'])
            ->where('customer_id', $this->customerId)
            ->where('company_id', $this->companyId)
            ->where('branch_id', $this->branchId)
            ->where('order_status', 'PG')
            ->first();
    }

    private function getProductDetails($productId)
    {
        return MerchantCatalogueProducts::with([
            'price:product_id,mpp_id,product_cost_price as cost_price,product_sell_price as unit_price',
            'tax',
            'discount',
            'taxInclusive:product_id,inclusive_flag,current_status'
        ])
            ->where('product_id', $productId)
            ->first();
    }

    private function calculateTaxValueSum($taxId)
    {
        $currentDate = Carbon::today()->toDateString();

        $taxComponentsWithValue = TaxMaster::with(['components.details' => function ($query) use ($currentDate) {
            $query->whereDate('tax_start_date', '<=', $currentDate)
                ->where(function ($q) use ($currentDate) {
                    $q->whereDate('tax_end_date', '>=', $currentDate)
                        ->orWhereNull('tax_end_date');
                })
                ->orderBy('tax_start_date', 'desc');
        }])->where('tax_id', $taxId)->get()->pluck('components')->flatten();

        return $taxComponentsWithValue->flatMap(function ($component) {
            return $component->details->pluck('tax_value');
        })->sum();
    }

    private function calculateDiscount($product, $unitPrice)
    {
        $currentDate = Carbon::today()->toDateString();
        $discountAmount = 0;

        if (!is_null($product->discount) && ($product->product_discount_flag == 'Y')) {
            $discount = DiscountMaster::with(['details' => function ($query) use ($currentDate) {
                $query->where('discount_start_date', '<=', $currentDate)
                    ->where(function ($q) use ($currentDate) {
                        $q->where('discount_end_date', '>=', $currentDate)
                            ->orWhereNull('discount_end_date');
                    });
            }, 'variation'])
                ->where('discount_id', $product->discount->discount_id)
                ->where('discount_status', 'A')
                ->orderBy('discount_start_date', 'desc')
                ->limit(1)
                ->first();

            if ($discount) {
                $discountType = $discount->discount_type;
                $discountValue = $discount->details->discount_value ?? 0;

                switch ($discountType) {
                    case 'P':
                        $discountAmount = round(($unitPrice * $discountValue) / 100, 2);
                        break;
                    case 'F':
                        $discountAmount = $discountValue;
                        break;
                }

                $product->discount = [
                    'discount_id' => $discount->discount_id,
                    'name' => $discount->details->discount_name,
                    'level' => $discount->level,
                    'type' => $discountType,
                    'value' => $discountValue,
                ];
            }
        }

        return $discountAmount;
    }

    private function calculateTaxes($product, &$netProductAmount, $taxValueSum, $inclusiveTax, $item)
    {
        $currentDate = Carbon::today()->toDateString();
        $taxes = [];
        $taxComponentsWithValue = TaxMaster::with(['components.details' => function ($query) use ($currentDate) {
            $query->whereDate('tax_start_date', '<=', $currentDate)
                ->where(function ($q) use ($currentDate) {
                    $q->whereDate('tax_end_date', '>=', $currentDate)
                        ->orWhereNull('tax_end_date');
                })
                ->orderBy('tax_start_date', 'desc');
        }])->where('tax_id', $product->tax->tax_id)->get()->pluck('components')->flatten();

        if ($inclusiveTax == 'Y') {
            $totalProductTaxAmount = round((($netProductAmount * $taxValueSum) / ($taxValueSum + 100)), 2);
            $netProductAmount -= $totalProductTaxAmount;
        } else {
            $totalProductTaxAmount = round((($netProductAmount * $taxValueSum) / 100), 2);
        }

        foreach ($taxComponentsWithValue as $taxComponent) {
            $taxValue = $taxComponent->details[0]->tax_value;
            $taxComponentName = $taxComponent->component_name;
            $taxComponentId = $taxComponent->tc_id;
            $taxAmount = round(($netProductAmount * $taxValue / 100), 2);

            $taxes[$taxComponentId]['amount'] = isset($taxes[$taxComponentId]['amount'])
                ? $taxes[$taxComponentId]['amount'] + round($taxAmount, 2)
                : round($taxAmount, 2);

            $taxes[$taxComponentId]['value'] = $taxValue;
            $taxes[$taxComponentId]['name'] = $taxComponentName;

            $taxAmount = round((($netProductAmount * $item->product_quantity) * $taxValue / 100), 2);

            if (isset($this->orderTaxes[$taxComponentId])) {
                $this->orderTaxes[$taxComponentId]['amount'] += round($taxAmount, 2);
            } else {
                $this->orderTaxes[$taxComponentId]['amount'] = round($taxAmount, 2);
            }

            $this->orderTaxes[$taxComponentId]['value'] = $taxValue;
            $this->orderTaxes[$taxComponentId]['name'] = $taxComponentName;
        }

        return [$taxes, $totalProductTaxAmount];
    }

    private function updateProductDetails($product, $netProductAmount, $taxes)
    {
        $product->price->unit_price = round($netProductAmount, 2);
        unset($product->tax);
        $product->tax = $taxes;
        return $product;
    }

    private function updateItemDetails($item, $netProductAmount, $totalProductTaxAmount, $product, $taxes)
    {
        $quantity = $item->product_quantity;
        $totalProductChargeAmount = 0.00;
        $item->detail = $product;
        $item->net_amount = $netProductAmount * $quantity;
        $item->tax_amount = round($totalProductTaxAmount * $quantity, 2);
        $item->charge_amount = $totalProductChargeAmount * $quantity;
        $item->total_amount = $item->net_amount + $item->tax_amount + $item->charge_amount;
        return $item;
    }

    private function accumulateTotals(&$cart, $item)
    {
        $cart->total_amount += $item->total_amount;
        $cart->net_amount += $item->net_amount;
        $cart->tax_amount += $item->tax_amount;
        $cart->charge_amount += $item->charge_amount;
        $cart->taxes = $this->orderTaxes;
    }

    private function generateResponse($cart)
    {
        $response = [
            'error' => false,
            'message' => '',
            'data' => [
                'order' => $cart
            ]
        ];

        return response()->json($response);
    }
}
