<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\JobsVaccancies;
use Dompdf\Exception;
use Illuminate\Http\Request;

class JobsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $request->validate([
                'company' => 'integer'
            ]);

            $data = $request->all();
            $companyId = $data['company'] ?? false;
            $jobs = JobsVaccancies::with([
                'createdBy:user_id,first_name,last_name',
                'company:company_id,company_name',
                'branch:branch_id,company_id,contact_id,branch_type,work_type',
                'branch.contact'
            ])
                ->when($companyId, function ($query, $companyId) {
                    $query->where('company_id', $companyId);
                })
                ->orderBy('id', 'DESC')
                ->get();
            $response = array(
                'message' => 'Jobs found',
                'data' => $jobs
            );
            return response()->json($response, 200);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], 422);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request) {}

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'company' => 'bail|required|integer',
                'branch' => 'required|integer',
                'title' => 'required|string',
                'description' => 'string',
                'loggedin_user_id' => 'required|integer'
            ]);
            $data = $request->all();

            $companyId = $data['company'];
            $branchId = $data['branch'];
            $jobTitle = $data['title'];
            $jobDescription = $data['description'] ?? '';
            $createdBy = $data['loggedin_user_id'];
            $randomNumber = rand(0, 99999);
            $jobCode = 'TJ-' . $randomNumber;
            JobsVaccancies::create([
                'code' => $jobCode,
                'title' => $jobTitle,
                'description' => $jobDescription,
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'created_by' => $createdBy
            ]);
            return response()->json(['message' => 'Successfully created job'], 200);
        } catch (Exception $ex) {
            echo 'Execption raised';
            return response()->json(['message' => $ex->getMessage()], 422);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($code)
    {
        try {
            $job = JobsVaccancies::with([
                'createdBy:user_id,first_name,last_name',
                'company:company_id,company_name',
                'branch:branch_id,company_id,contact_id,branch_type,work_type',
                'branch.contact'
            ])
                ->where('code', $code)
                ->first();

            if (!$job) {
                throw new Exception('Job not found');
            }

            $response = [
                'message' => 'Job found',
                'data'    => $job
            ];

            return response()->json($response, 200);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], 422);
        }
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($code)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $code)
    {
        try {

            $request->validate([
                'title' => 'bail|required|string',
                'description' => 'required|string',
                'loggedin_user_id' => 'required|integer'
            ]);

            $isJobsExisting = JobsVaccancies::where('code', $code)->exists();
            if (!$isJobsExisting) {
                throw new Execption('Job does not exist');
            }
            $data = $request->all();
            $jobTitle = $data['title'];
            $jobDescription = $data['description'];
            $updatedBy = $data['loggedin_user_id'];

            JobsVaccancies::where('code', $code)
                ->update([
                    'title' => $jobTitle,
                    'description' => $jobDescription,
                    'updated_by' => $updatedBy
                ]);
            $response = array(
                'message' => 'Job updated successfully'
            );
            return response()->json($response, 200);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($code)
    {
        try {
            JobsVaccancies::where('code', $code)->delete();
            $response = array(
                'message' => 'Job deleted successfully'
            );
            return response()->json($response, 200);
        } catch (Exception $ex) {
            return response()->json(['message' => $ex->getMessage()], 422);
        }
    }
}
