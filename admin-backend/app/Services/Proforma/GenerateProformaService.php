<?php

namespace App\Services\Proforma;

use App\Repositories\ProformaRepository;

class GenerateProformaService
{
    protected $proformaRepository;


    public function __construct(ProformaRepository $proformaRepository)
    {
        $this->proformaRepository = $proformaRepository;
    }

    public function createProforma($data, $summary)
    {
        // echo json_encode($summary);exit;
        // Step 1: Save the proforma header details
        $summary['company_id'] = $data['company_id'];
        $summary['branch_id'] = $data['branch_id'];
        $summary['customer_id'] = $data['customer_id'];
        $summary['executive_id'] = $data['executive_id'];

        $proforma = $this->proformaRepository->createProforma($summary);

        // Step 2: Process proforma items 
        foreach ($summary['items'] as $item) {

            // Step 2.0: Save proforma items / product details 
            $this->proformaRepository->addProductToProforma($proforma->id, $item);

            // Step 2.1: Save taxes details
            $this->proformaRepository->addProductTax($proforma->id, $item);

            // Step 2.2: Save discount details 
            $this->proformaRepository->addProductDiscount($proforma->id, $item);

            // Step 2.3: Save charge details
            $this->proformaRepository->addProductCharge($proforma->id, $item);
        }
        $proforma = $this->proformaRepository->detailProforma($proforma->id);
        return $proforma;
    }
}
