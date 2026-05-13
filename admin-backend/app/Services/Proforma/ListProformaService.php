<?php 
namespace App\Services\Proforma;

use App\Repositories\ProformaRepository;

class ListProformaService
{
    protected $proformaRepository;

    public function __construct(ProformaRepository $proformaRepository)
    {
        $this->proformaRepository = $proformaRepository;
    }

    public function getProformaList($filters = [])
    { 
        return $this->proformaRepository->paginateProformas($filters);
    }
}
