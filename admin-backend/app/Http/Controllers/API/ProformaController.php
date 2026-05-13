<?php

namespace App\Http\Controllers\API;;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Requests\CalculateSummaryRequest;
use App\Http\Requests\GenerateProformaRequest;
use App\Http\Requests\ListProformaRequest;
use App\Http\Requests\ProformaDetailRequest;
use App\Http\Resources\ProformaResource;
use App\Http\Resources\ProformaDetailResource;
use App\Services\Proforma\ProformaService;
use Barryvdh\DomPDF\Facade\Pdf;
use App\ProformaDetails;
use Log;


class ProformaController extends Controller
{
    protected $proformaService;

    public function __construct(ProformaService $proformaService)
    {
        $this->proformaService = $proformaService;
    }

    public function calculateSummary(CalculateSummaryRequest $request)
    {
        Log::info(json_encode($request->all()));
        try {
            $summary = $this->proformaService->calculateProformaSummary($request->validated());
            return response()->json([
                'error_flag' => false,
                'error_message' => '',
                'data' => $summary
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error_flag' => false,
                'error_message' => 'Error calculating summary: ' . $e->getMessage()
            ], 500);
        }
    }
    public function generate(GenerateProformaRequest $request)
    {
        // try {
        $summary = $this->proformaService->generateProforma($request->validated());
        // echo json_encode($summary);exit;
        return response()->json(['message' => 'Successfully created proforma', 'data' => new ProformaResource($summary)], 200);
        // } catch (\Exception $e) {
        //     return response()->json([
        //         'error_flag' => false,
        //         'error_message' => 'Error generating proforma: ' . $e->getMessage()
        //     ], 500);
        // }
    }

    public function export(Request $request, $proformaNo)
    {

        try {
            $proforma = $this->proformaService->exportProforma($proformaNo);
            // $proforma = (new ProformaResource($summary))->toArray(request()); // Convert to array
            // return response()->json($proforma);
            // return view('invoices.proforma_template2', compact('proforma'));

            // Load the PDF view
            $pdf = Pdf::loadView('invoices.proforma_template2', compact('proforma'));
            // Set paper size and orientation
            $pdf->setPaper('a4', 'portrait');
            $pdf->setOptions([
                'dpi' => 150,
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true
            ]);

            // Download PDF without storing on server
            // return $pdf->download('Invoice-' . $proformaNo . '.pdf');
            // Generate the PDF content as a string
            $pdfContent = $pdf->output();

            // Set headers explicitly
            return response($pdfContent, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="Invoice-' . $proformaNo . '.pdf"',
                'Content-Length' => strlen($pdfContent),
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0',
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error exporting proforma: ' . $e->getMessage()], 500);
        }
    }

    public function list(ListProformaRequest $request)
    {
        // dd($request->validated());  
        try {
            $data = $this->proformaService->listProformas($request->validated());
            // $invoice = new InvoiceController;
            // if ($trialCheck = $invoice->checkTrial($data['company_id'])) {
            //     return $trialCheck; // This ends the function if there's an error
            // }
            // echo json_encode($data['proformas']);exit;
            return response()->json(['total_count' => $data['total_count'], 'proformas' => ProformaResource::collection($data['proformas'])], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error listing proformas: ' . $e->getMessage()], 500);
        }
    }
    public function show($proformaNo)
    {
        try {
            $proforma = $this->proformaService->getProformaDetails($proformaNo);

            return response()->json([
                'success' => true,
                'message' => 'Profoma details',
                'data' => new ProformaResource($proforma)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error fetching proforma details',
                'errors' => $e->getMessage()
            ], 500);
        }
    }

    public function convertProformaToOrder($proformaId)
    {
        try {
            $this->proformaService->convertProformaToOrder($proformaId);
            return response()->json(['message' => 'Proforma converted to order successfully.'], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error converting proforma to order: ' . $e->getMessage()], 500);
        }
    }
}
