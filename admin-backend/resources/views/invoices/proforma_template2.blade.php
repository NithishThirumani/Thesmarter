<!-- resources/views/invoices/tax_invoice.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proforma Invoice #{{ $proforma->proforma_no }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 2cm 2cm 2cm 2cm;
        }
        
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            color: #333;
            position: relative;
            font-size: 12px;
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
            width: 100%;
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
        
        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }
        
        .address-container {
            margin: 20px 0;
            width: 100%;
        }
        
        .address-box {
            padding: 15px;
            background-color: #f8fafc;
            border-radius: 5px;
            margin-bottom: 10px;
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
            border-bottom: 1px solid #e2e8f0;
            padding: 5px 0;
            width: 100%;
            overflow: hidden;
        }
        
        .totals-label {
            font-weight: bold;
            color: #4a5568;
            float: left;
            width: 60%;
        }

        .totals-value {
            float: right;
            width: 40%;
            text-align: right;
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

        .left-column {
            width: 48%;
            float: left;
        }
        
        .right-column {
            width: 48%;
            float: right;
        }
        .bank-details {
            margin: 20px 0;
            padding: 15px;
            background-color: #f0f9ff;
            border-radius: 5px;
            border-left: 4px solid #2c5282;
        }
        
        .bank-title {
            font-weight: bold;
            color: #2c5282;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .bank-info {
            display: flex;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        
        .bank-column {
            width: 48%;
        }
        
        .bank-item {
            margin-bottom: 8px;
        }
        
        .bank-label {
            font-weight: bold;
            color: #4a5568;
            display: inline-block;
            width: 140px;
        }
        
    </style>
</head>
<body>
    <div class="watermark">{{ $proforma->company->company_name }}</div>
    
    <div class="header clearfix">
        <table style="border: none; margin: 0;">
            <tr style="background: none;">
                <td style="border: none; padding: 0; width: 40%;">
                    @php
                        $logoPath = "https://business.bizwy.in/v1/assets/companies/" . $proforma->company->company_id . "/" . $proforma->company->company_logo;
                        $logoExists = !empty($proforma->company->company_logo);
                        
                    @endphp

                    @if($logoExists)
                        <img src="{{ $logoPath }}" alt="Company Logo" class="logo" onerror="this.style.display='none'; this.insertAdjacentHTML('afterend', '<div style=\'font-size: 28px; font-weight: bold; color: #2c5282; font-family: Helvetica, Arial, sans-serif;\'>{{ strtoupper($proforma->company->company_name) }}</div>');">
                    @else
                        <div style="font-size: 28px; font-weight: bold; color: #2c5282; font-family: 'Helvetica', 'Arial', sans-serif;">
                            {{ strtoupper($proforma->company->company_name) }}
                        </div>
                    @endif
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
    
    <div class="address-container clearfix">
        <div class="left-column">
            <div class="address-box">
                <div class="address-title">BILLED TO</div>
                <div>{{ $proforma->customer->first_name." ".$proforma->customer->last_name }}</div>
                <div>{{ $proforma->customer->defaultContactUser?->contactDetails->address1 }}, {{$proforma->customer->defaultContactUser?->contactDetails->area }}</div>
                <div>{{ $proforma->customer->defaultContactUser?->contactDetails->city }}, {{ $proforma->customer->defaultContactUser?->contactDetails->state }} {{ $proforma->customer->defaultContactUser?->contactDetails->pincode }}</div>
                <div>{{ $proforma->customer->defaultContactUser?->contactDetails->country }}</div>
                 @php
            
                $details = optional($proforma->customerAdditionalDetail)['details'] ?? [];

                $gstin = $details['GSTIN']['value'] ?? null;
                $isGstinPrintable = $details['GSTIN']['is_printable'] ?? false;

                $pan = $details['PAN']['value'] ?? null;
                $isPanPrintable = $details['PAN']['is_printable'] ?? false;
                
            @endphp
             @if($gstin)
                    <div style="margin-top: 5px;"><strong>GSTIN:</strong> {{ $gstin }}</div>
                @endif
            </div>
        </div>
        
        <div class="right-column">
            <div class="address-box">
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
    </div>
    
    <div class="info-container clearfix">
        <div class="info-item">
            <span class="info-label">Invoice Date:</span> {{ date('d F Y', strtotime($proforma->proforma_date_time)) }}
        </div>
        <div class="info-item">
            {{-- <span class="info-label">Due Date:</span> {{ date('d M Y', strtotime($invoice->due_date)) }} --}}
        </div>
    </div>

    @php
        $subTotal = 0;
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
        <div class="totals-row clearfix">
            <span class="totals-label">Sub Total</span>
            <span class="totals-value">{{ number_format($subTotal, 2) }}</span>
        </div>
       
        @if(!empty($taxes))
        @foreach($taxes as $key => $value)
            <div class="totals-row clearfix">
                <span class="totals-label">{{$value['name']}} ({{ $value['value'] }}%)</span>
                <span class="totals-value">{{ number_format($value['amount'], 2) }}</span>
            </div>
        @endforeach
        @endif
        
        @if(!is_null($proforma->discount_id))
        <div class="totals-row clearfix">
            <span class="totals-label">Discount</span>
            <span class="totals-value">- {{ number_format($proforma->discountAmount, 2) }}</span>
        </div>
        @endif
        
        <div class="grand-total totals-row clearfix">
            <span class="totals-label">TOTAL</span>
            <span class="totals-value">{{ number_format($proforma->total_amount, 2) }}</span>
        </div>
    </div>
    
   {{-- Amount in Words --}}
    @php
        // Function to convert number to words
        function numberToWords($number) {
            $ones = array(
                0 => '', 1 => 'One', 2 => 'Two', 3 => 'Three', 4 => 'Four', 5 => 'Five',
                6 => 'Six', 7 => 'Seven', 8 => 'Eight', 9 => 'Nine', 10 => 'Ten',
                11 => 'Eleven', 12 => 'Twelve', 13 => 'Thirteen', 14 => 'Fourteen',
                15 => 'Fifteen', 16 => 'Sixteen', 17 => 'Seventeen', 18 => 'Eighteen',
                19 => 'Nineteen'
            );
            
            $tens = array(
                0 => '', 2 => 'Twenty', 3 => 'Thirty', 4 => 'Forty', 5 => 'Fifty',
                6 => 'Sixty', 7 => 'Seventy', 8 => 'Eighty', 9 => 'Ninety'
            );
            
            if ($number < 20) {
                return $ones[$number];
            } elseif ($number < 100) {
                return $tens[intval($number / 10)] . ' ' . $ones[$number % 10];
            } elseif ($number < 1000) {
                return $ones[intval($number / 100)] . ' Hundred ' . numberToWords($number % 100);
            } elseif ($number < 100000) {
                return numberToWords(intval($number / 1000)) . ' Thousand ' . numberToWords($number % 1000);
            } elseif ($number < 10000000) {
                return numberToWords(intval($number / 100000)) . ' Lakh ' . numberToWords($number % 100000);
            } else {
                return numberToWords(intval($number / 10000000)) . ' Crore ' . numberToWords($number % 10000000);
            }
        }
        
        $totalAmount = floor($proforma->total_amount);
        $paiseAmount = round(($proforma->total_amount - $totalAmount) * 100);
        $amountInWords = trim(numberToWords($totalAmount));
        
        if ($paiseAmount > 0) {
            $amountInWords .= ' Rupees and ' . trim(numberToWords($paiseAmount)) . ' Paisa Only';
        } else {
            $amountInWords .= ' Rupees Only';
        }
    @endphp
    
    <div class="amount-in-words">
        <strong>Amount in Words:</strong> {{ ucfirst($amountInWords) }}
    </div>
    {{-- Bank Details Section --}}
    <div class="bank-details">
        <div class="bank-title">BANK DETAILS</div>
        <div class="bank-info">
            <div class="bank-column">
                <div class="bank-item">
                    <span class="bank-label">Account Name:</span>
                    {{ $proforma->company->account_name ?? '' }}
                </div>
                <div class="bank-item">
                    <span class="bank-label">Account Number:</span>
                    {{ $proforma->company->account_number ?? '' }}
                </div>
            </div>
            <div class="bank-column">
                <div class="bank-item">
                    <span class="bank-label">IFSC Code:</span>
                    {{ $proforma->company->bank_code ?? '' }}
                </div>
                
            </div>
        </div>
        @if(isset($proforma->company->bank_name))
        <div class="bank-item" style="margin-top: 10px;">
            <span class="bank-label">Bank Name:</span>
            {{ $proforma->company->bank_name }}
        </div>
        @endif
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