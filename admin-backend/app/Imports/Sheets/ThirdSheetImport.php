<?php

namespace App\Imports\Sheets;

use App\UserCompanies;
use App\UserDetail;
use App\UserLogin;
use App\ContactDetail;
use App\UserContact;
use Session;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class ThirdSheetImport implements ToCollection, WithStartRow
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
        $this->companyId = 2;// Session::get('companyId');

        $users = UserLogin::all();
        $companyUsers = UserCompanies::where('company_id', $this->companyId)->get();
        foreach ($collection as $row) {

            // Check if user exists in the system 
            $userDetails = $users->where('user_mobile', $row[1])->first();

            if ($userDetails == null) {
                // If NO
                // Insert data into User details
                $user = UserDetail::create([
                    'first_name' => $row[3],
                    'last_name' => $row[4],
                    'user_dob' => $row[5] ?? NULL,
                    'marital_status' => $row[7] ?? NULL,
                    'user_gender' => $row[6] ?? NULL,
                    'user_status' => 1,
                    'created_company_id' => $this->companyId
                ]);
                
                // DB::commit(); 
                $password = 230718;
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert data into user login
                UserLogin::create([
                    'user_id' => $user->user_id,
                    'user_mobile' => $row[1],
                    'email'=>$row[2],
                    'user_pin' => $hashedPassword
                ]);
                // Insert data into contact details
                $contact = ContactDetail::create([
                    'phone' => $row[1],
                    'email' => $row[2],
                    'pincode' => $row[8] ?? NULL,
                    'city' => $row[12] ?? NULL,
                    'country' => $row[14] ?? NULL,
                    'address1' => $row[9] ?? NULL,
                    'area' => $row[11] ?? NULL,
                ]);
                // Insert data into user contact
                UserContact::create([
                    'user_id' => $user->user_id,
                    'contact_id' => $contact->contact_id,
                    'contact_type' => 'H',
                    'default_contact' => 1,
                ]);
                $UserId = $user->user_id;
            } else {
                // Check if user mapped to the company 
                // if NO
                // Insert data into user companies
                // print_r(json_encode($userDetails));
                // exit;
                $UserId = $userDetails->user_id;
            }
            $UserMapping = $companyUsers->where('user_id', $UserId)->where('company_id', $this->companyId)->first();
            if ($UserMapping == null) {
                switch (strtolower($row[0])) {
                    case 'owner':
                        $userType = 3;
                        break;
                    case 'executive':
                        $userType = 4;
                        break;
                    default:
                        $userType = 5;
                        break;
                }
                UserCompanies::create([
                    'company_id' => $this->companyId,
                    'user_type' => $userType,
                    'user_id' => $UserId,
                    'status' => 1,
                    'creator_id' => 1,
                ]);

                // Send email to user that thier account has been added 


            }
        }
        
    }
}
