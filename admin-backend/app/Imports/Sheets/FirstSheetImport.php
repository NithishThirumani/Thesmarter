<?php

namespace App\Imports\Sheets;

use App\CompanyDetail;
use Session;
use App\LineOfBusiness;
use App\Country;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;
use DB;

class FirstSheetImport implements ToCollection, WithStartRow
{
    /**
     * @param Collection $collection
     */
    public static $companyId;
    public function startRow(): int
    {
        return 3;
    }
    public function collection(Collection $collection)
    {
        // List of LOB's
        $lobs = LineOfBusiness::all();


        foreach ($collection as $row) {

            // LOB id from collection
            $lobId = NULL;

            if (!empty($row[2])) {
                $lobname = ucfirst($row[2]);
                $lob = $lobs->where('lob_name', $lobname)->first();
                $lobId = $lob['lob_id'];
            }
            // Company Timing 
            $openingTime = !empty($row[7]) ? date('H:i:s', strtotime($row[7])) : "09:00:00";
            $closingTime = !empty($row[8]) ? date('H:i:s', strtotime($row[8])) : "21:00:00";
            // Get comapny country 
            $country = Country::where('country_name', $row[21])->first();


            $company = CompanyDetail::create([
                'company_name' => $row[0],
                'company_logo' => $row[1],
                'company_business_id' => $lobId,
                'company_revenue_id' => 0,
                'comapny_website' => $row[4],
                'company_gstin' => $row[5],
                'company_pan' => $row[6],
                'company_status' => 'A',
                'company_dawn' => $openingTime,
                'company_dusk' => $closingTime,
                'company_timeslice' => $row[9],
                'company_marketing_message' => $row[10],
                'latitude' => $row[11],
                'longitude' => $row[12],
                'radius' => $row[13],
                'bank_name' => $row[14],
                'bank_code' => $row[15],
                'account_name' => $row[16],
                'account_number' => $row[17],
                'discount_tax_inclusive' => $row[18],
                'feedback_flag' => $row[19],
                'customer_app' => $row[20],
                'country_id' => $country->country_id
            ]);


            Session::put('companyId', $company->company_id);
            FirstSheetImport::$companyId = $company->company_id;
            // echo "First sheet seession" . Session::get('companyId');
            break;
        }
    }
}
