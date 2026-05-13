<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\ContactDetail;
use App\DiscountMaster;
use App\DiscountDetail;
use App\Jobs\SendEmail;
use App\OrderDetail;
use App\TaxComponents;
use App\OrderProductTaxes;
use App\TaxDetail;
use App\UserLogin;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\DB;
use Log;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\View;

class InvoiceController extends Controller
{
    // protected $invoiceNo;
    // protected $order;
    // protected $orderTaxes = array();
    // protected $taxComponents = array();
    // protected $productLevelTotalDiscount = 0;

    protected $invoiceNo;
    protected $proformaNo;
    protected $proforma;
    protected $email;
    protected $orde;
    protected $orderTaxes = array();
    protected $taxComponents = array();
    protected $productLevelTotalDiscount = 0;

    public function getCustomerEmails(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'order_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }
        $this->invoiceNo = $data['order_id'];


        // Get invoiced customers email address 
        // $emailIds = ContactDetail::select('email')
        //     ->whereHas('user.order', function ($query) {
        //         $query->where('order_id', $this->invoiceNo);
        //     })
        //     ->where('email', '!=', null)
        //     // ->get();

        //     ->get()->pluck('email')->all();
        $emailIds = UserLogin::select('email')->whereHas('order', function ($query) {
            $query->where('order_id', $this->invoiceNo);
        })
            ->whereNotNull('email')
            ->get()->pluck('email')->all();

        if (empty($emailIds)) {
            return response()->json(['error' => false, 'messsage' => 'No email ids found', 'emails' => array()]);
        }
        return  response()->json(['error' => false, 'messsage' => 'Email ids', 'emails' => $emailIds]);
    }
    public function transaction($invoiceNo)
    {
        $this->invoiceNo = $invoiceNo;

        $this->order = $this->details();

        $this->net_amount = 0;

        // Get Order Level Discount 
        $discountId = is_null($this->order->discount_id) ? 0 : $this->order->discount_id;
        $orderDate = $this->order->order_date;
        $discountDetail = array();

        $discount = DiscountMaster::where('discount_id', $discountId)->first();
        if ($discount != null && $discount->discount_type == 'A') {
            $discountDetail =  DiscountDetail::where('discount_id', $discountId)
                ->where('discount_start_date', '<=', $discountId)
                ->where(function ($query) use ($orderDate) {
                    $query->where(DB::raw('DATE(discount_end_date)'), '>=', $orderDate)
                        ->orWhereNull(DB::raw('DATE(discount_end_date)'));
                })->first();
        } else if ($discount != null) {
            $discountDetail = DiscountDetail::where('discount_id', $discountId)->first();
        }
        $this->order->order_level_discount = $discountDetail;
         $i = 0;
        // var_dump($order);exit;
        $this->order->items->map(function ($item, $key) use ($discount) {

            $orderId = $item->order_id;
            $productId = $item->product_id;
            $item->product->product_name = html_entity_decode(htmlspecialchars_decode($item->product->product_name, ENT_QUOTES));
            // Get Product Tax Components
            // $taxes = TaxDetail::with(['component'])
            //     ->whereHas('order', function ($query) use ($orderId, $productId) {
            //         $query->where('order_id', $orderId)->where('product_id', $productId);
            //     })->get();
            $taxes = OrderProductTaxes::where('order_id', $orderId)->where('product_id', $productId)->get();


            // $taxes = TaxComponents::with( ['details:tc_id,td_id,tax_value'])
            // ->whereHas('details',function($query) use ($orderId, $productId){
            //     $query->whereHas('order', function ($query) use ($orderId, $productId) {
            //         $query->where('order_id', $orderId)->where('product_id', $productId);
            //     });


            //    })->get();
            $item->tax = $taxes;

            // Get Product Discount Components
            // if($item->discount_id >0 && $item->product_id ==4837){ 
            $item->discount = DiscountMaster::with(['details'])
                ->whereHas('details.order', function ($query) use ($orderId, $productId) {
                    $query->where('order_id', $orderId)->where('product_id', $productId);
                })->get();

            // echo json_encode($item->discount);exit;
            // }
            // Net Product Amount 
            // $this->net_amount = $item->net_amount + $item->discount_amount; // Sale Amount 
            $item->net_amount =  number_format((float) $item->net_amount, 2, '.', '');
            $item->sale_amount = $item->net_amount + $item->discount_amount; // Sale Amount 

            if ($discount != null && $this->order->company->discount_tax_inclusive == 'Y' && $item->discount_id == 0) { // discount tax incl and order level disocunt 
                $this->net_amount = number_format(floatval(($item->net_amount + $item->discount_amount) / $item->product_quantity), 2, '.', '');
            }
            // Product level discount
            $item->discount_amount = number_format((float) $item->discount_amount, 2, '.', '');
            $this->productLevelTotalDiscount += $item->discount_amount;
            // Total Amount 
            $item->total_amount = $item->net_amount + $item->product_charge_amount;
            /*
             NEED T0 CALCULATE TAX AGAIN HERE 
             */

            if (count($taxes) > 0) {

                foreach ($taxes as $tax) {
                   
                    $componentName = $tax->name . $tax->value;
                    // if (!in_array($componentName, $this->taxComponents)) {
                    //     array_push($this->taxComponents, $componentName);

                    //     $this->orderTaxes[$componentName] = 0;
                    //     $this->orderTaxes[$componentName . 'value'] =  $tax->tax_value;
                    //     $this->orderTaxes[$componentName . 'name'] = $tax->component->component_name;
                    // }
                    // $taxed_amount = (($item->total_amount *  $tax->tax_value) / 100);

                    // $taxComponentAmount  = round(($this->orderTaxes[$componentName] + $taxed_amount), 2);
                   $this->$orderTaxes[] = array(
                        'component_name'=>$tax->name,
                        'value'=>$tax->value,
                        'amount'=>number_format((float) $tax->amount, 2, '.', '')
                   );
                   
                }
            }

            $item->total_amount = number_format(($item->total_amount), 2, '.', ',');
            return $item;
        });
        print_r(json_encode($this->orderTaxes));exit;
        // Total Discount 
        $this->order->discount_amount = floatval($this->order->discount_amount);

        //   $this->productLevelTotalDiscount = floatval($this->productLevelTotalDiscount);
        //   $this->order->discount_amount =$this->order->discount_amount-$this->productLevelTotalDiscount;
        //   var_dump($this->productLevelTotalDiscount);exit; 
        $this->order->discount_amount = number_format((float)$this->order->discount_amount, 2, '.', '');
        // Charge amount not getting implemented - Refer PreviewBill.php




        // Sub Total 
        $this->order->subTotal = number_format(($this->order->net_amount), 2, '.', ',');
        // Taxes 
        $this->order->tax_components = $this->taxComponents;
        $this->order->taxes = $this->orderTaxes;

        //Total Amount 
        $this->order->total_amount = number_format(($this->order->total_amount), 2, '.', ',');
        $this->order->subject = $this->order->company->company_name . " - Paid Invoice";
        // echo json_encode($this->order);exit;
        return $this->order;
    }
    public function proformaTransaction()
    {

        $this->proforma = $this->proformaDetails();

        $net_amount = 0;

        // Get Order Level Discount 
        $discountId = is_null($this->proforma->discount_id) ? 0 : $this->proforma->discount_id;
        $orderDate = $this->proforma->order_date;
        $discountDetail = array();

        $discount = DiscountMaster::where('discount_id', $discountId)->first();
        if ($discount != null && $discount->discount_type == 'A') {
            $discountDetail =  DiscountDetail::where('discount_id', $discountId)
                ->where('discount_start_date', '<=', $discountId)
                ->where(function ($query) use ($orderDate) {
                    $query->where(DB::raw('DATE(discount_end_date)'), '>=', $orderDate)
                        ->orWhereNull(DB::raw('DATE(discount_end_date)'));
                })->first();
        } else if ($discount != null) {
            $discountDetail = DiscountDetail::where('discount_id', $discountId)->first();
        }
        $this->proforma->order_level_discount = $discountDetail;

        // var_dump($order);exit;
        $this->proforma->items->map(function ($item, $key) use ($discount) {

            $orderId = $item->order_id;
            $productId = $item->product_id;
            $item->product->product_name = html_entity_decode(htmlspecialchars_decode($item->product->product_name, ENT_QUOTES));
            // Get Product Tax Components
            $taxes = TaxDetail::with(['component'])
                ->whereHas('order', function ($query) use ($orderId, $productId) {
                    $query->where('order_id', $orderId)->where('product_id', $productId);
                })->get();


            // $taxes = TaxComponents::with( ['details:tc_id,td_id,tax_value'])
            // ->whereHas('details',function($query) use ($orderId, $productId){
            //     $query->whereHas('order', function ($query) use ($orderId, $productId) {
            //         $query->where('order_id', $orderId)->where('product_id', $productId);
            //     });


            //    })->get();
            $item->tax = $taxes;

            // Get Product Discount Components
            $item->discount = DiscountMaster::with(['details'])
                ->whereHas('details.order', function ($query) use ($orderId, $productId) {
                    $query->where('order_id', $orderId)->where('product_id', $productId);
                });

            // Net Product Amount 
            // $this->net_amount = $item->net_amount + $item->discount_amount; // Sale Amount 
            $item->net_amount =  number_format((float) $item->net_amount, 2, '.', '');
            $item->sale_amount = $item->net_amount + $item->discount_amount; // Sale Amount 

            if ($discount != null && $this->proforma->company->discount_tax_inclusive == 'Y' && $item->discount_id == 0) { // discount tax incl and order level disocunt 
                $net_amount = number_format(floatval(($item->net_amount + $item->discount_amount) / $item->product_quantity), 2, '.', '');
            }
            // Product level discount
            $item->discount_amount = number_format((float) $item->discount_amount, 2, '.', '');
            $this->productLevelTotalDiscount += $item->discount_amount;
            // Total Amount 
            $item->total_amount = $item->net_amount + $item->product_charge_amount;
            /*
             NEED T0 CALCULATE TAX AGAIN HERE 
             */

            if (count($taxes) > 0) {

                foreach ($taxes as $tax) {
                    $taxComponentAmount = 0;
                    if (!in_array($tax->component->component_name, $this->taxComponents)) {
                        array_push($this->taxComponents, $tax->component->component_name);

                        $this->orderTaxes[$tax->component->component_name] = 0;
                        $this->orderTaxes[$tax->component->component_name . 'value'] =  $tax->tax_value;
                    }
                    $taxed_amount = (($item->total_amount *  $tax->tax_value) / 100);

                    $taxComponentAmount  = round(($this->orderTaxes[$tax->component->component_name] + $taxed_amount), 2);
                    $this->orderTaxes[$tax->component->component_name] = number_format((float) $taxComponentAmount, 2, '.', '');
                }
            }

            $item->total_amount = number_format(($item->total_amount), 2, '.', ',');
            return $item;
        });
        // Total Discount 
        $this->proforma->discount_amount = floatval($this->proforma->discount_amount);

        //   $this->productLevelTotalDiscount = floatval($this->productLevelTotalDiscount);
        //   $this->order->discount_amount =$this->order->discount_amount-$this->productLevelTotalDiscount;
        //   var_dump($this->productLevelTotalDiscount);exit; 
        $this->proforma->discount_amount = number_format((float)$this->proforma->discount_amount, 2, '.', '');
        // Charge amount not getting implemented - Refer PreviewBill.php




        // Sub Total 
        $this->proforma->subTotal = number_format(($this->proforma->net_amount), 2, '.', ',');
        // Taxes 
        $this->proforma->tax_components = $this->taxComponents;
        $this->proforma->taxes = $this->orderTaxes;
        //Total Amount 
        $this->proforma->total_amount = number_format(($this->proforma->total_amount), 2, '.', ',');
        $this->proforma->subject = $this->proforma->company->company_name . " - Proforma Invoice";
        return $this->proforma;
    }
    public function mail(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'order_id' => 'required',
        ]);
        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }
        $this->invoiceNo = $data['order_id'];
        $email = $data['email'];

        // $data['order_id'] = $this->transaction($this->invoiceNo);
        //         return view('mails.invoice')
        //         ->with('data',$data['order_id']);
        // exit;
        // return response()->json($myData);
        // $body =  $this->order->toArray();
        // return response()->json($body);
        // exit;


        SendEmail::dispatch($email, $this->invoiceNo);

        return response()->json(['error' => false, 'message' => 'Mail sent'], 200);
    }
    public function print($invoiceNo)
    {

        $this->invoiceNo = $invoiceNo;
        $orderDetails = $this->transaction($this->invoiceNo);
// return response()->json($orderDetails);

        return view('mails.invoice')->with('data', $orderDetails);
    }
    public function proforma($proformaNo)
    {
        $this->proformaNo = $proformaNo;
        $proformaDetails = $this->proformaTransaction();
        return view('mails.proforma')->with('data', $proformaDetails);
    }
    public function share($invoiceNo)
    {
        $this->invoiceNo = $invoiceNo;
        $orderDetails = $this->transaction($this->invoiceNo);

        $pdf = new Dompdf();
        $html = View::make('mails.invoice', ['data' => $orderDetails])->render();
        $pdf->loadHtml($html);
        $pdf->setPaper('A4', 'portrait'); // Adjust paper size and orientation as needed
        $pdf->render();

        $filePath = "/var/www/bizwy_in/v1/assets/invoices/" . $this->invoiceNo . ".pdf";
        file_put_contents($filePath, $pdf->output());
        $webURL = "https://business.bizwy.in/v1/assets/invoices/" . $this->invoiceNo . ".pdf";

        return response()->json(['error' => false, 'url' => $webURL]);
    }
    private function details()
    {
        return  OrderDetail::with([
            'company:company_id,company_name,company_website,discount_tax_inclusive,company_marketing_message',
            'branch:branch_id,work_type,contact_id',
            'branch.contact:contact_id,area,address1,city,state,country,phone,email',
            'customer:user_id,first_name,last_name',
            'executive:user_id,first_name,last_name',
            'items',
            'items.product:product_id,product_name,product_brand',
            'items.unitPrice:mpp_id,product_cost_price,product_sell_price',
            'payment:order_id,payment_mode_id,amount_paid,amount_balance,amount_returned',
            'payment.paymentMode:payment_id,payment_name',
            'miscellaneous',
            'additional'
        ])
            ->where('order_id', $this->invoiceNo)
            ->where('order_status', 'CP')
            ->first();
    }
    private function proformaDetails()
    {
        return  ProformaDetails::with([
            'company:company_id,company_name,company_website,discount_tax_inclusive,company_marketing_message',
            'branch:branch_id,work_type,contact_id',
            'branch.contact:contact_id,area,address1,city,state,country,phone,email',
            'customer:user_id,first_name,last_name',
            'executive:user_id,first_name,last_name',
            'items',
            'items.product:product_id,product_name,product_brand',
            'items.unitPrice:mpp_id,product_cost_price,product_sell_price',
            'miscellaneous',
        ])
            ->where('order_id', $this->proformaNo)
            ->where('order_status', 'C')
            ->first();
    }
    public function generateOrder()
    {
        $x =  OrderDetail::with([
            'company:company_id,company_name,company_website,discount_tax_inclusive,company_marketing_message',
            'branch:branch_id,work_type,contact_id',
            'branch.contact:contact_id,area,address1,city,state,country,phone,email',
            'customer:user_id,first_name,last_name',
            'executive:user_id,first_name,last_name',
            'items',
            'items.product:product_id,product_name,product_brand',
            'items.unitPrice:mpp_id,product_cost_price,product_sell_price',
            'payment:order_id,payment_mode_id,amount_paid,amount_balance,amount_returned',
            'payment.master:payment_id,payment_name',
            'miscellaneous',
            'additional'
        ])
            ->where('order_id', 240700000009)
            ->where('order_status', 'PG')
            ->first();

        return response()->json($x);
    }
    public function latestCompletedInvoice(Request $request)
    {

        $data = $request->all();
        $validator = Validator::make($data, [
            'company_id' => 'required|integer',
            'loggedin_user_id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }
        $company_id = $data['company_id'];
        // if ($trialCheck = $this->checkTrial($company_id)) {
        //     return $trialCheck; // This ends the function if there's an error
        // }
        // Last 5 completed orders 
        $latestOrders = OrderDetail::select('order_id', 'order_date', 'order_time', 'order_status')
            ->where('company_id', $company_id)
            ->where('order_status', 'CP')
            ->orderBy('order_date', 'desc')
            ->orderBy('order_time', 'desc') // You can use order_id or created_at, depending on the sorting needs
            ->take(5) // Limit to the latest 5 records
            ->withCount('items')
            ->get();
        $response = array(
            'error' => false,
            'message' => '',
            'data' => $latestOrders
        );
        return response()->json($response, 200);
    }
    public function checkTrial($companyId)
    {
        $trialData = DB::table('company_trail')
            ->select('trial_start', 'trial_expires', 'isPaid')
            ->where('company_id', $companyId)
            ->first();

        if (!$trialData) {
            return response()->json([
                'error' => true,
                'error_message' => 'Company not found.',
            ], 200);
        }

        // If trial_start is null, initialize trial
        if (is_null($trialData->trial_start)) {
            $now = Carbon::now();
            $expires = $now->copy()->addDays(3);

            DB::table('company_trail')
                ->where('company_id', $companyId)
                ->update([
                    'trial_start' => $now,
                    'trial_expires' => $expires,
                ]);

            return true; // Allow request
        }

        // If trial expired and not paid
        if (Carbon::now()->greaterThan($trialData->trial_expires) && $trialData->isPaid == 0) {
            return response()->json([
                'error' => true,
                'message' => 'Your trial period has expired. Please subscribe to continue using the app.',
            ], 200);
        }

        return true; // Allow request
    }
}
