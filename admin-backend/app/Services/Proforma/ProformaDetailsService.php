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

    public function execute(int $proformaId)
    {
        return $this->proformaRepository->findDetailedProforma($proformaId);
    }
}
