@php 
  //echo ($proforma->customer->defaultContactUser->contactDetails->city);exit;
@endphp

<!-- resources/views/invoices/tax_invoice.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proforma Invoice #{{ $proforma->proforma_no }}</title>
    <style>
        @page {
            margin: 0cm 0cm;
        }
        
        body {
            margin-top: 2cm;
            margin-bottom: 2cm;
            margin-right: 2cm;
            margin-left: 2cm;
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            position: relative;
        }
        
        .watermark {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 100px;
            color: rgba(200, 200, 200, 0.15);
            z-index: -1;
        }
        
        .header {
            padding: 10px 0;
            border-bottom: 2px solid #2c5282;
        }
        
        .logo {
            max-height: 70px;
            max-width: 200px;
        }
        
        .invoice-title {
            font-size: 24px;
            font-weight: bold;
            color: #2c5282;
            margin-bottom: 5px;
        }
        
        .invoice-number {
            font-size: 16px;
            color: #4a5568;
        }
        
        .status {
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: bold;
            display: inline-block;
            text-transform: uppercase;
            font-size: 12px;
        }
        
        .status-paid {
            background-color: #c6f6d5;
            color: #2f855a;
        }
        
        .status-pending {
            background-color: #feebc8;
            color: #c05621;
        }
        
        .status-overdue {
            background-color: #fed7d7;
            color: #c53030;
        }
        
        .address-container {
            margin: 20px 0;
            display: flex;
        }
        
        .address-box {
            width: 100%;
            padding: 15px;
            background-color: #f8fafc;
            border-radius: 5px;
        }
        
        .address-title {
            font-weight: bold;
            margin-bottom: 5px;
            color: #2c5282;
        }
        
        .info-container {
            margin: 20px 0;
        }
        
        .info-item {
            margin-bottom: 5px;
        }
        
        .info-label {
            font-weight: bold;
            color: #4a5568;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        
        th {
            background-color: #2c5282;
            color: white;
            padding: 10px;
            text-align: left;
        }
        
        td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        tr:nth-child(even) {
            background-color: #f7fafc;
        }
        
        .totals-container {
            width: 350px;
            margin-left: auto;
            margin-right: 0;
        }
        
        .totals-row {
           display:flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .totals-label {
            font-weight: bold;
            color: #4a5568;
        }
        
        .grand-total {
            font-size: 18px;
            font-weight: bold;
            color: #2c5282;
            padding: 10px 0;
            border-top: 2px solid #2c5282;
            border-bottom: 2px solid #2c5282;
        }
        
        .amount-in-words {
            margin: 20px 0;
            padding: 10px;
            background-color: #f8fafc;
            border-radius: 5px;
            font-style: italic;
        }
        
        .terms-container {
            margin-top: 40px;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }
        
        .terms-title {
            font-weight: bold;
            color: #2c5282;
            margin-bottom: 10px;
        }
        
        .footer {
            position: fixed;
            bottom: 0;
            width: 100%;
            text-align: center;
            padding: 10px 0;
            font-size: 12px;
            color: #718096;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>
   
    <div class="watermark">{{ $proforma->company->company_name }}</div>
   
    
    <div class="header">
        <table style="border: none; margin: 0;">
            <tr style="background: none;">
                <td style="border: none; padding: 0; width: 40%;">
                    <img src="https://bizwy.in/images/bizwy-logo.png" alt="Company Logo" class="logo">
                </td>
                <td style="border: none; padding: 0; text-align: right; vertical-align: top;">
                    <div class="invoice-title">PROFORMA INVOICE</div>
                    <div class="invoice-number">#{{$proforma->proforma_no }}</div>
                </td>
            </tr>
        </table>
    </div>
    
    <div style="text-align: right; margin-top: 10px;">
        @if($proforma->proforma_status == 'C')
            <span class="status status-paid">Issued</span>
        @else
            <span class="status" style="background-color: #e2e8f0; color: #4a5568;">{{ $summary->proforma_status }}</span>
        @endif
    </div>
    
    <div class="address-container">
        <div class="address-box" style="float: left; width: 50%;" >
            <div class="address-title">BILLED TO</div>
            <div>{{ $proforma->customer->first_name." ".$proforma->customer->last_name }}</div>
            <div>{{ $proforma->customer->defaultContactUser?->contactDetails->address1 }}, {{$proforma->customer->defaultContactUser?->contactDetails->area }}</div>
            <div>{{ $proforma->customer->defaultContactUser?->contactDetails->city }}, {{ $proforma->customer->defaultContactUser?->contactDetails->state }} {{ $proforma->customer->defaultContactUser?->contactDetails->pincode }}</div>
            <div>{{ $proforma->customer->defaultContactUser?->contactDetails->country }}</div>
        </div>
        
        <div class="address-box"  style="float: right; width: 50%;">
            <div class="address-title">SELLER</div>
             <div>{{ $proforma->company->company_name}}</div>
            <div>{{ $proforma->branch->contact->address1 }}, {{$proforma->branch->contact->area}}</div>
            <div>{{ $proforma->branch->contact->city }}, {{ $proforma->branch->contact->state }} {{ $proforma->branch->contact->pincode }}</div>
            <div>{{ $proforma->branch->contact->country }}</div>
            @if($proforma->company->company_gstin)
                <div style="margin-top: 5px;"><strong>GSTIN:</strong> {{ $proforma->company->company_gstin }}</div>
            @endif
        </div>
    </div>
    <br>
    <div class="info-container">
        <div class="info-item">
            <span class="info-label">Invoice Date:</span> {{ date('d F Y', strtotime($proforma->proforma_date_time)) }}
        </div>
        <div class="info-item">
            {{-- <span class="info-label">Due Date:</span> {{ date('d M Y', strtotime($invoice->due_date)) }} --}}
        </div>
        {{-- @if($invoice->po_number)
        <div class="info-item">
            <span class="info-label">PO Number:</span> {{ $invoice->po_number }}
        </div>
        @endif --}}
    </div>
    @php
            $subTotal =0;
            $taxes = [];
        @endphp
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 42%;">Item Description</th>
                <th style="width: 12%;">HSN Code</th>
                <th style="width: 8%;">Qty</th>
                <th style="width: 15%;">Unit Price</th>
                <th style="width: 18%;">Total</th>
            </tr>
        </thead>
        <tbody>
       
            @foreach($proforma->items as $index => $item)
           
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $item->product->product_name}}</td>
                <td>{{ $item->product->product_hsn_code ?? '' }}</td>
                <td>{{ $item->product_quantity }}</td>
                <td>{{ number_format($item->base_amount, 2) }}</td>
                <td>{{ number_format($item->net_amount, 2) }}</td>
                @php 
                    $subTotal = $subTotal+$item->net_amount;
                    foreach($item->tax as $tax)
                    {
                        $taxAmount = $tax->amount;
                         // Accumulate tax amounts and details
                        $taxes[$tax->td_id]['amount'] =
                            isset($taxes[$tax->td_id]['amount'])
                            ? $taxes[$tax->td_id]['amount'] + round($taxAmount, 2)
                            : round($taxAmount, 2);
                        $taxes[$tax->td_id]['name']=$tax->name;
                        $taxes[$tax->td_id]['value']=$tax->value;
                    }
                @endphp 
            </tr>
            @endforeach
        </tbody>
    </table>
    
    <div class="totals-container">
        <div class="totals-row">
            <div class="totals-label">Sub Total</div>
            <div>{{ number_format($subTotal, 2) }}</div>
        </div>
       
        @if(!empty($taxes))
        
        @foreach($taxes as $key => $value)
            <div class="totals-row">
            <div class="totals-label">{{$value['name']}} ({{ $value['value'] }}%)</div>
            <div>{{ number_format($value['amount'], 2) }}</div>
        </div>
        @endforeach
        @endif
        @if(!is_null($proforma->discount_id))
        <div class="totals-row">
            <div class="totals-label">Discount</div>
            <div>- {{ number_format($proforma->discountAmount, 2) }}</div>
        </div>
        @endif
        <div class="grand-total totals-row">
            <div class="totals-label">TOTAL</div>
            <div>{{ number_format($proforma->total_amount, 2) }}</div>
        </div>
    </div>
    
    <div class="amount-in-words">
        {{-- <strong>Amount in words:</strong> {{ ucfirst($amountInWords) }} --}}
    </div>
    
    <div class="terms-container">
        <div class="terms-title">Terms & Conditions</div>
        <div>
            This proforma invoice is issued for reference only and does not constitute a final sales agreement. Payment must be completed before order processing and shipment.
        </div>
    </div>
    
    <div class="footer">
        {{ $proforma->company->company_name }} | {{ $proforma->branch->contact->email }} | {{ $proforma->branch->contact->phone }}
    </div>
</body>
</html>