<?php

namespace App\Http\Controllers;

use App\CompanyDetail;
use App\Imports\CompanyBatchImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Http\Request;

class BatchImportController extends Controller
{
    public function index()
    {
        $companies =CompanyDetail::select('company_id as company','company_name as name')
                ->where('company_id','!=',1)
                ->get();
        return view('import',compact('companies'));
    }
    public function import(Request $request)
    {

        // Validate the input
        $request->validate([
            'select_file'  => 'required|mimes:xls,xlsx'
        ]);
       
        // return back()->withErrors(['msg'=>'Access Denied . Authorized Only to Admin !!']);
        $path = $request->file('select_file')->getRealPath();
        

        // Excel::import(new CompanyBatchImport, $path);
        // Excel::import(new CompanyBatchImport, $path, \Maatwebsite\Excel\Excel::XLSX);
        Excel::import(
            new CompanyBatchImport,
            $request->file('select_file')->store('files')
        );
        return back()->with('success', 'Excel Data Imported successfully.');
    }
}
