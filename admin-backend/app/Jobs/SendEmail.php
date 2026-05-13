<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Mail\EmailForQueuing;
use Mail;
use App\OrderDetail;
use App\TaxComponents;
use App\ContactDetail;
use App\DiscountMaster;
use App\DiscountDetail;
use App\Http\Controllers\API\InvoiceController;

class SendEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $details;
    protected $body ;
    protected $subject ;

    protected $invoiceNo;
    protected $email;
    protected $orde;
    protected $orderTaxes = array();
    protected $taxComponents = array();
    protected $productLevelTotalDiscount = 0;

    public $tries = 1;
    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($email,$invoiceNo)
    {
        //
        $this->email = $email;
        $this->invoiceNo = $invoiceNo;
        
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
       
        $invoice = new InvoiceController();
        $order = $invoice->transaction($this->invoiceNo);
     
        $emailQueue = new EmailForQueuing($order->subject,$order);
        Mail::to($this->email)->send($emailQueue);
    }
}
