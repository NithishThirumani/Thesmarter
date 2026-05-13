<?php

namespace App\Imports\Sheets;

use Session;

use App\ContactDetail;
use App\BranchDetail;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class SecondSheetImport implements ToCollection, WithStartRow
{
    private $companyId;
    /**
     * @param Collection $collection
     */

    public function startRow(): int
    {
        return 3;
    }
    public function collection(Collection $collection)
    {
        $this->companyId = Session::get('companyId');
        $i = 0;
        foreach ($collection as $row) {
            if ($i === 0) {
                $branchType = 'H';
            }

            //Insert into contact details 
            $contact = ContactDetail::create([
                'phone' => $row[3] ?? NULL,
                'email' => $row[4] ?? NULL,
                'address1' => $row[5] ?? NULL,
                'area' => $row[7] ?? NULL,
                'city' => $row[8] ?? NULL,
                'country' => $row[10] ?? NULL,
                'pincode' => $row[11] ?? NULL,
            ]);

            //Insert into branch details
            $branch = BranchDetail::create([
                'company_id' => $this->companyId,
                'branch_status' => 'A',
                'contact_id' => $contact->contact_id,
                'branch_type' => $branchType,
                'work_type' => 1
            ]);
            $i++;
        }
    }
}
