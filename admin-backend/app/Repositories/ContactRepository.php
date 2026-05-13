<?php
namespace App\Repositories;

use App\ContactDetail;

class ContactRepository
{
    public function getContactDetailById(int $contactId):ContactDetail
    {
        return ContactDetail::where('contact_id',$contactId)->first();
    }
}