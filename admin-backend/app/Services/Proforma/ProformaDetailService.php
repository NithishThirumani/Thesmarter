<?php

namespace App\Services\Proforma;

use App\Repositories\ProformaRepository;

class ProformaDetailService
{
    protected $proformaRepository;

    public function __construct(ProformaRepository $proformaRepository)
    {
        $this->proformaRepository = $proformaRepository;
    }

    public function getDetailedProforma(string $proformaNo)
    {
        $proforma = $this->proformaRepository->detailProforma(null, $proformaNo);
        $taxes = [];
        foreach ($proforma->items as $item) {
            $tax = $item->tax;
            if (!empty($tax)) {
                foreach ($tax as $t) {
                    $taxAmount = $t->amount;
                    // Accumulate tax amounts and details
                    $taxes[$t->td_id]['amount'] =
                        isset($taxes[$t->td_id]['amount'])
                        ? $taxes[$t->td_id]['amount'] + round($taxAmount, 2)
                        : round($taxAmount, 2);
                    $taxes[$t->td_id]['name'] = $t->name;
                    $taxes[$t->td_id]['value'] = $t->value;
                }
            }
        }
        $proforma->taxes = $taxes;
        return $proforma;
    }
}
