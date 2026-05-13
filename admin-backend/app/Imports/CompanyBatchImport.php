<?php

namespace App\Imports;

use Session;
use Illuminate\Support\Collection;
// use Maatwebsite\Excel\Concerns\ToCollection;
use App\Imports\Sheets\FirstSheetImport;
use App\Imports\Sheets\SecondSheetImport;
use App\Imports\Sheets\ThirdSheetImport;
use App\Imports\Sheets\FourthSheetImport;
use App\Imports\Sheets\FifthSheetImport;
use App\Imports\Sheets\SixthSheetImport;
use App\Imports\Sheets\SeventhSheetImport;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class CompanyBatchImport implements WithMultipleSheets
{
    public $companyId;
    public function sheets(): array
    {
        return [
            // "Company Details" => new FirstSheetImport(),
            // "Branch Details" => new SecondSheetImport(),
            // "User Details" => new ThirdSheetImport(),
            // "Universal Tags" => new FourthSheetImport(),
            // "Taxes" => new FifthSheetImport(),
            "Catalog" => new SixthSheetImport(),
            // "Inventory" => new SeventhSheetImport()
        ];
    }
}
