<?php

namespace App\Imports\Sheets;

use Session;
use App\TaxMaster;
use App\TaxComponents;
use App\TaxDetail;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class FifthSheetImport implements ToCollection, WithStartRow
{
    private $companyId;
    /**
     * @param Collection $collection
     */
    public function startRow(): int
    {
        return 4;
    }
    public function collection(Collection $collection)
    {
        if (!empty($collection)) {
            $this->companyId = 2;//Session::get('companyId');

            // Get all Taxes of the company 
            $taxes = TaxMaster::where('company_id', $this->companyId)->get();
            foreach ($collection as $row) {
                $tax = $taxes->where('tax_name',"=", $row[1])->all();
                if (!empty($tax)) {
                    // Log tax already exists 
                    continue;
                } 
                // Inset into tax master 
                $tax = TaxMaster::create(
                    [
                        'company_id' => $this->companyId,
                        'tax_name' => (string)$row[1],
                    ]
                );
               
                for ($column = 3; !empty($row[$column]); $column++) {
                    // Insert into tax components
                    $taxComponentName = $row[$column];
                    $taxComponent = TaxComponents::create([
                        'tax_id' => $tax->id,
                        'component_name' => $taxComponentName,
                    ]);
                   
                    $column++;
                    $taxValue = number_format((float)$row[$column], 2, '.', '');
                    $today = date('Y-m-d');

                    // Insert into tax details
                    TaxDetail::create([
                        'tc_id' => $taxComponent->tc_id,
                        'tax_value' => $taxValue,
                        'tax_start_date' => $today,
                        'tax_end_date' => NULL
                    ]);
                }
            }
        }
    }
}
