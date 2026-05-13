<html>
 @php 
                            print_r($data->taxes);
                             exit;
                            @endphp
<body>
    <style>
        body {
            background: #eee;
            font-family: 'calibri', san-serif;
            font-size: 14px;
        }

        .wrapper {
            width: 100%;
            margin: 0 auto;
            padding: 0px;
        }

        @media (min-width:324px) {
            .wrap-div {
                width: 90%;
                margin: 0 auto;
            }
        }

        @media (min-width:544px) {
            .wrap-div {
                width: 90%;
                margin: 0 auto;
            }
        }

        @media (min-width:768px) {
            .wrap-div {
                width: 65%;
                margin: 0 auto;
            }
        }

        @media (min-width:1200px) {
            .wrap-div {
                width: 45%;
                margin: 0 auto;
            }
        }

    </style>

    <div class="wrapper">
        <div class="wrap-div">

            @php
            $order = $data;
                                    

            @endphp
            <table cellpadding="0" cellspacing="0" style="width:100%; background:#fff; border:1px solid #d6d6d6;">
                <tr>
                    <td colspan="3" style="background-color:#fff; border-bottom:1px dashed #d6d6d6; padding:10px 0px 12px 10px; margin:0px 0px 0px 0px; text-align:center;">
                        <h1 style="margin:0px; padding:0px; color:#000000; font-weight:normal;">{{$order->company->company_name}}</h1>
                        <div style="clear:both;"></div>
                        <div style="font-size:12px; margin:5px 0px 0px 0px;">
                            <span>{{$order->branch->contact->area!="" ? $order->branch->contact->area.", ":""}}{{$order->branch->contact->address1}},<br /></span>
                            <span>{{$order->branch->contact->city}},{{$order->branch->contact->state}},{{$order->branch->contact->country}}</span><br />
                            @if($order->branch->contact->phone!="")
                            <span><b>Phone No:</b> {{ $order->branch->contact->phone }}</span>&nbsp;, &nbsp;
                            @endif
                            @if($order->branch->contact->email!="")
                            <span><b>Email:</b> {{$order->branch->contact->email}}</span><br />
                            @endif
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="background-color:#fff; border-bottom:1px dashed #d6d6d6; padding:10px 0px 12px 10px; margin:0px 0px 0px 0px;">
                        <table width="100%" style="font-size:13px; padding:5px 10px;  border-radius:3px; -moz-border-radius:3px;">
                            <tr>
                                <td align="left"><b>Transaction ID:</b> {{$order->order_id}}</td>
                            </tr>
                            <tr>
                                <td align="left"><b>Date:</b> {{date('d F, Y',strtotime($order->order_date))}} </td>
                            </tr>
                            <tr>
                                <td align="left"><b>Customer:</b> {{$order->customer->first_name.' '.$order->customer->last_name}} </td>
                            </tr>

                            <tr>
                                <td align="left"><b>Payment Type:</b> {{$order->payment[0]->paymentMode->payment_name}} </td>
                            </tr>
                        </table>
                    </td>
                </tr>

                <tr>
                    <td colspan="3" style="padding:10px 20px 0px 20px;">
                        <table width="100%" style="font-size:13px; padding:5px 10px;  border-radius:3px; -moz-border-radius:3px;">
                            <tr>
                                <th align="left" style="border-bottom:1px dashed #ccc; line-height:30px;">Description</th>
                                <th align="center" style="border-bottom:1px dashed #ccc; line-height:30px;">QTY</th>
                                <th align="center" style="border-bottom:1px dashed #ccc; line-height:30px;">Unit Rate</th>
                                <th align="center" style="border-bottom:1px dashed #ccc; line-height:30px;">Total</th>
                            </tr>
                            @php
                            $totalSaleAmount = 0;

                            @endphp
                            @foreach($order->items as $item)
                            <tr>
                                @if($item->discount_id > 0)
                                <td style="line-height:20px; width:50%">{{$item->product->product_name}}<br><span style="text-align:right"> Product Discount @ {{$item->discount[0]->details[0]->discount_value}} %</span></td>
                                @else
                                <td style="line-height:20px; width:50%">{{$item->product->product_name}}</td>
                                @endif
                                <td style="line-height:20px;width=10%" align="center">{{$item->product_quantity}}</td>
                                <td align="center" style="line-height:20px;" align="center;width=20%">{{number_format(($item->sale_amount/$item->product_quantity),2,".",",")}}
                                    @if($item->discount_id>0)
                                    <br>(-){{number_format(($item->discount_amount/$item->product_quantity),2,".",",")}}
                                    @endif

                                </td>
                                <td align="center" style="line-height:20px;" align="center;width=20%"> {{number_format(($item->sale_amount),2,".",",")}}
                                    @if($item->discount_id>0)
                                    @php
                                    $item->sale_amount-=$item->discount_amount;
                                    @endphp
                                    <br>(-){{number_format(($item->discount_amount),2,".",",")}}
                                    @endif

                                </td>
                            </tr>
                            @php
                            $totalSaleAmount +=$item->sale_amount ;

                            @endphp
                            @endforeach
                        </table>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="padding:10px 20px 0px 20px; border-bottom:1px dashed #ccc;">
                        <table width="100%" style="font-size:13px; padding:5px 0px;">
                            <tr>
                                <th align="left" style="border-top:1px dashed #ccc; line-height:30px;"></th>
                                <th align="left" style="border-top:1px dashed #ccc; line-height:30px;"></th>
                                <th align="center" style="border-top:1px dashed #ccc; line-height:30px;"></th>
                                <th align="center" style="border-top:1px dashed #ccc; line-height:30px;"></th>
                            </tr>
                            <tr>
                                <td align="right" style="line-height:20px; width:80%">Sub Total:</th>
                                <td align="center" style="line-height:20px;">{{ number_format(($totalSaleAmount), 2, '.', ',')}}</th>
                            </tr>
                            @if($order->discount_id > 0)
                            <tr>
                                <td align="right" style="line-height:20px; width:80%">Order Level Savings : {{$order->order_level_discount->discount_value}} %</th>
                                <td align="center" style="line-height:20px;">(-){{ number_format(($order->discount_amount), 2, '.', ',')}}</th>
                            </tr>
                            @endif
                            @if($order->charge_amount > 0)
                            @foreach($order->miscellaneous as $charge)
                            <tr>
                                <td align="right" style="line-height:20px; width:80%">Service Charge @ 5 % : </th>
                                <td align="center" style="line-height:20px;">{{ number_format(($order->charge_amount), 2, '.', ',')}}</th>
                            </tr>
                            @endforeach
                            @endif
                            <!-- Tax Components-->
                            @if(count($order->tax_components)>0)
                            <tr>
                                <td colspan="3" style="padding:10px 20px 0px 20px; border-bottom:1px dashed #ccc;">

                            <tr>
                                <td align="left"><b>GST Summary</b></td>
                            </tr>
                            @php
                            /*
                            <tr style="text-align: right;">
                                <td align="right" style="line-height:20px;">
                                    VAT @ 10 %
                                </td>
                                <td align="center" style="line-height:20px;">
                                    {{ number_format(($order->tax_amount), 2, '.', ',')}}
                                </td>
                            </tr>
                            */
                            @endphp
                           
                            @foreach($order->tax_components as $component)
                            <tr style="text-align: right;">
                                <td align="right" style="line-height:20px;">
                                    {{$order->taxes[$component.'name']}} @ {{$order->taxes[$component.'value']}} %
                                </td>
                                <td align="center" style="line-height:20px;">
                                    {{$order->taxes[$component]}}
                                </td>
                            </tr>
                            @endforeach


                    </td>
                </tr>
                @endif

            </table>
            </td>
            </tr>

            <tr style="font-size:22px;" colspan="3">
                <td align="right" style="width:70%">Bill Total :</td>
                <td align="center">{{ $order->total_amount}}</td>
            </tr>
            <tr style="" colspan="3">
                <td align="right" style="width:70%"></td>
                <td align="center">All values are in EUR</td>
            </tr>
            <tr style="background:black; padding:25px 0px 0px 0px;">
                <td valign="top" colspan="3">
                    <table cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                            <td>
                                <p style="font-family:calibri;color:#fff; font-size:12px; text-align:center; margin:0px; padding:5px 70px 5px 70px; border-top:1px dotted #d6d6d6;">Powered by <a href="https://www.bizwy.com" target="_blank" style="text-decoration:none; font-weight:bold;"><span style="color:#ffff">Bizwy</span></a><br />
                                    <p></p>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            </table>


        </div>
    </div>
</body>

</html>
