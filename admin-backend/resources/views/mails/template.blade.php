<html>

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
                            <span><b>Phone No:</b> 7052596700</span>&nbsp;, &nbsp;
                            @endif
                            @if($order->branch->contact->email!="")
                            <span><b>Email:</b> {{$order->branch->contact->email}}</span><br />
                            @endif
                            <span><b>Date - </b>{{date('d F, Y',strtotime($order->order_date))}} </span><br />

                            <span>
                                <b>Transaction-ID : </b> {{$order->order_id}}
                                @if(!empty($order->executive))
                                <b>Billed By : </b> {{$order->executive->first_name." ".$order->executive->last_name}}
                                @endif
                            </span><br />
                            <span><b>Payment Type : </b> Card</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td colspan="3" style="padding:10px 20px 0px 20px;">
                        <table width="100%" style="font-size:13px; padding:5px 10px;  border-radius:3px; -moz-border-radius:3px;">
                            <tr>
                                <th align="left" style="border-bottom:1px dashed #ccc; line-height:30px;">Description</th>
                                <th align="center" style="border-bottom:1px dashed #ccc; line-height:30px;">QTY</th>
                                <th align="center" style="border-bottom:1px dashed #ccc; line-height:30px;">Rate</th>
                                <th align="center" style="border-bottom:1px dashed #ccc; line-height:30px;">Total</th>
                            </tr>
                            @foreach($order->items as $item)
                            <tr>
                                @if($item->discount_id > 0)
                                <td style="line-height:20px; width:50%">{{$item->product->product_name}}<br><span style="text-align:right">Discount @ {{$item->discount->details->discount_value}} %</span></td>
                                @endif
                                <td style="line-height:20px; width:50%">{{$item->product->product_name}}</td>
                                <td style="line-height:20px;width=10%" align="center">{{$item->product_quantity}}</td>
                                <td align="center" style="line-height:20px;" align="center;width=20%">{{$item->net_amount}}</td>
                                <td align="center" style="line-height:20px;" align="center;width=20%">{{$item->net_amount}}</td>
                            </tr>
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
                            @if($order->discount_id > 0)
                            <tr>
                                <td align="right" style="line-height:20px; width:80%">Order Level Savings : {{$order->order_level_discount->discount_value}}</th>
                                <td align="center" style="line-height:20px;">{{$order->discount_amount}}</th>
                            </tr>
                            @endif
                            <tr>
                                <td align="right" style="line-height:20px; width:80%">Sub Total:</th>
                                <td align="center" style="line-height:20px;">{{$order->subTotal}}</th>
                            </tr>
                            @if(count($order->tax_components)>0)
                            @foreach($order->tax_components as $component)
                            <tr style="text-align: right;">
                                <td align="right" style="line-height:20px;">
                                    {{$component}} @ {{$order->taxes[$component.'value']}} %
                                </td>
                                <td align="center" style="line-height:20px;">
                                    {{$order->taxes[$component]}}
                                </td>
                            </tr>
                            @endforeach
                            @endif
                            <tr>
                                <td align="right" style="line-height:20px;"><b>Total :</b></th>
                                <td align="center" style="line-height:20px;"><b>{{$order->total_amount}}</b></th>
                            </tr>

                        </table>
                    </td>
                </tr>
                <tr style="font-size:22px;" colspan="3">
                    <td align="right" style="width:70%">Total :</td>
                    <td align="center">{{$order->total_amount}}</td>
                </tr>
                <tr style="background:black; padding:25px 0px 0px 0px;">
                    <td valign="top" colspan="3">
                        <table cellpadding="0" cellspacing="0" width="100%">
                            <tr>
                                <td>
                                    <p style="font-family:calibri;color:#fff; font-size:12px; text-align:center; margin:0px; padding:5px 70px 5px 70px; border-top:1px dotted #d6d6d6;">This email was sent by <a href="https://www.thesmartr.com" target="_blank" style="text-decoration:none; font-weight:bold;"><sup style="color:#ffff">the</sup><span style="color:#ffff">smartr</span></a><br />
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