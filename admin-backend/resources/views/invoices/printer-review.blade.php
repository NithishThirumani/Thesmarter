<!-- resources/views/invoices/tax_invoice_thermal.blade.php -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Invoice #{{ $order->order_id }}</title>
    <style>
        @media print {
            @page {
                margin: 5mm;
                size: auto;
            }
            body {
                font-size: 11px;
                font-family: monospace;
            }
        }

        body {
            font-size: 11px;
            font-family: monospace;
            margin: 5px;
            width: 100%;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .line {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .right {
            text-align: right;
        }

        .flex {
            display: flex;
            justify-content: space-between;
        }

        .small {
            font-size: 10px;
        }

        .status {
            padding: 2px 5px;
            border: 1px solid black;
            border-radius: 4px;
            display: inline-block;
        }
    </style>
</head>
<body>

    <div class="center bold">{{ $order->company->company_name }}</div>
    <div class="center small">
        {{ $order->branch->contact->address1 ?? '' }},
        {{ $order->branch->contact->city ?? '' }}
        <br>
        {{ $order->branch->contact->phone ?? '' }} | {{ $order->branch->contact->email ?? '' }}
    </div>

    <div class="line"></div>

    <div>
        <div><strong>Order #:</strong> {{ $order->order_id }}</div>
        <div><strong>Date:</strong> {{ date('d M Y', strtotime($order->order_date)) }}</div>
        <div><strong>Status:</strong> 
            @if($order->order_status == 'CP')
                Completed
            @else
                {{ $summary->order_status ?? 'Pending' }}
            @endif
        </div>
        <div><strong>Payment:</strong> 
            @php
                $hasDue = collect($order->payment)->contains(fn($p) => $p->payment_mode_id == 3);
            @endphp
            {{ $hasDue ? 'DUE' : 'PAID' }}
        </div>
    </div>

    <div class="line"></div>

    <div><strong>Customer:</strong></div>
    <div>{{ $order->customer->first_name }} {{ $order->customer->last_name }}</div>
    <div class="small">
        @php
            $contact = $order->customer->defaultContactUser->contactDetails;
            $addr = array_filter([$contact->address1 ?? '', $contact->area ?? '', $contact->city ?? '', $contact->state ?? '', $contact->pincode ?? '', $contact->country ?? '']);
        @endphp
        {{ implode(', ', $addr) }}
    </div>

    <div class="line"></div>

    <div><strong>Items:</strong></div>
    @php $subTotal = 0; $taxes = []; @endphp
    @foreach($order->items as $item)
        <div>{{ $item->product->product_name }}</div>
        <div class="flex small">
            <span>Qty: {{ $item->product_quantity }}</span>
            <span>Rate: {{ number_format($item->base_price, 2) }}</span>
        </div>
        @if(collect($item->discount)->isNotEmpty())
            @foreach($item->discount as $discount)
                <div class="small">Discount: 
                    {{ $discount->value }}{{ $discount->type == 'F' ? ' Flat' : '%' }}
                    (-{{ number_format($discount->amount, 2) }})
                </div>
            @endforeach
        @endif
        <div class="right">Total: {{ number_format($item->net_amount, 2) }}</div>
        @php 
            $subTotal += $item->net_amount;
            foreach($item->tax as $tax) {
                $taxes[$tax->td_id]['amount'] = ($taxes[$tax->td_id]['amount'] ?? 0) + round($tax->amount, 2);
                $taxes[$tax->td_id]['name'] = $tax->name;
                $taxes[$tax->td_id]['value'] = $tax->value;
            }
        @endphp
        <div class="line"></div>
    @endforeach

    <div class="flex"><span>Subtotal:</span><span>{{ number_format($subTotal, 2) }}</span></div>

    @if(collect($order->discount)->isNotEmpty())
        <div class="flex small">
            <span>Order Discount:</span>
            <span>-{{ number_format($order->discount[0]->amount, 2) }}</span>
        </div>
    @endif

    @if(!empty($order->charges))
        @foreach($order->charges as $charge)
            <div class="flex small">
                <span>{{ $charge['name'] }}:</span>
                <span>{{ number_format($charge['amount'], 2) }}</span>
            </div>
        @endforeach
    @endif

    @if(!empty($taxes))
        @foreach($taxes as $tax)
            <div class="flex small">
                <span>{{ $tax['name'] }} ({{ $tax['value'] }}%):</span>
                <span>{{ number_format($tax['amount'], 2) }}</span>
            </div>
        @endforeach
    @endif

    <div class="flex bold" style="margin-top: 5px;">
        <span>GRAND TOTAL:</span>
        <span>₹{{ number_format($order->total_amount, 2) }}</span>
    </div>

    <div class="line"></div>

    @if(collect($order->payment)->isNotEmpty())
        <div><strong>Payment Details:</strong></div>
        @foreach($order->payment as $payment)
            @php 
                $paymentType = match($payment->payment_mode_id) {
                    1 => 'Cash',
                    2 => 'Card',
                    3 => 'Due',
                    default => 'Online',
                };
            @endphp
            <div class="small">Mode: {{ $paymentType }}</div>
            @if($payment->payment_reference)
                <div class="small">Txn ID: {{ $payment->payment_reference }}</div>
            @endif
            <div class="small">Date: {{ date('d M Y', strtotime($payment->created_dtm)) }}</div>
            <div class="small">Amount: ₹{{ number_format($payment->amount_paid, 2) }}</div>
            <div class="small">Status: {{ $payment->payment_mode_id == 3 ? 'Due' : 'Paid' }}</div>
            <div class="line"></div>
        @endforeach
    @endif

    <div class="center small">
        Thank you for your purchase!
        <br>
        Powered by Bizwy
    </div>
</body>
</html>
