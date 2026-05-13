<?php
namespace App\Services\Proforma;

use PDF;
use Mail;
use App\Repositories\ProformaRepository;

class ExportProformaService
{
    protected $proformaRepository;

    public function __construct(ProformaRepository $proformaRepository)
    {
        $this->proformaRepository = $proformaRepository;
    }

    public function execute(int $proformaId)
    {
        $proforma = $this->proformaRepository->findById($proformaId);
        // echo json_encode($proforma);exit
        $pdf = PDF::loadView('proforma.export', compact('proforma'));
        
        // Email the PDF
        Mail::send('emails.proforma', compact('proforma'), function($message) use ($pdf) {
            $message->to('customer@example.com')
                    ->subject('Proforma Invoice')
                    ->attachData($pdf->output(), "proforma.pdf");
        });

        return $pdf->download('proforma.pdf');
    }
}
