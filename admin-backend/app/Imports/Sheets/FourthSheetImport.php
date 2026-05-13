<?php

namespace App\Imports\Sheets;

use Session;
use App\UniversalTags;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class FourthSheetImport implements ToCollection, WithStartRow
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
        $this->companyId = 2;//Session::get('companyId');
        foreach ($collection as $row) {

            switch (strtolower($row[2])) {
                case "product":
                    $tagType = 1;
                    break;
                default:
                    $tagType = NULL;
                    break;
            }
            $tagName = $row[1];
            UniversalTags::updateOrCreate(
                [
                    'company_id' => $this->companyId,
                    'tag_name' => $tagName,

                ],
                [
                    'module_id' => $tagType,
                    'created_by' => 1,
                    'tag_logo' => NULL

                ]
            );
        }
    }
}
