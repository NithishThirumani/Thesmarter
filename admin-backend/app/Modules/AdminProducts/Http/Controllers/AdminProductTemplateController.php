<?php

namespace App\Modules\AdminProducts\Http\Controllers;

use App\CompanyDetail;
use App\Http\Controllers\Controller;
use App\Modules\AdminProducts\Exceptions\ProductTemplateException;
use App\Modules\AdminProducts\Services\ProductTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class AdminProductTemplateController extends Controller
{
    private ProductTemplateService $templateService;

    public function __construct(ProductTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * GET /admin/products/template-meta?company_id=
     *
     * Prerequisite payload for enabling the catalogue template download UX.
     */
    public function templateMeta(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|min:1',
        ]);

        $payload = $this->templateService->readiness((int) $validated['company_id']);

        return response()->json([
            'success' => true,
            'data' => $payload,
        ]);
    }

    /**
     * GET /admin/products/template?company_id=&catalogue_id=
     *
     * Streamed XLSX download.
     */
    public function template(Request $request)
    {
        $validated = $request->validate([
            'company_id' => 'required|integer|min:1',
            'catalogue_id' => 'required|integer|min:1',
        ]);

        try {
            $spreadsheet = $this->templateService->generateTemplate(
                (int) $validated['company_id'],
                (int) $validated['catalogue_id']
            );
        } catch (ProductTemplateException $e) {
            $code = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 422;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $code);
        }

        $companyId = (int) $validated['company_id'];
        $catalogueId = (int) $validated['catalogue_id'];
        $company = CompanyDetail::query()->find($companyId);
        $filename = $this->buildProductTemplateFilename($company, $companyId, $catalogueId);

        return response()->streamDownload(function () use ($spreadsheet): void {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Human-friendly ASCII filename from company label (stored Content-Disposition by Laravel).
     */
    private function buildProductTemplateFilename(?CompanyDetail $company, int $companyId, int $catalogueId): string
    {
        $slug = Str::slug((string) ($company ? $company->company_name : ''));
        if ($slug === '') {
            $slug = 'company-'.$companyId;
        }
        $slug = substr($slug, 0, 60);

        return $slug.'-catalogue-'.$catalogueId.'-product-upload-template.xlsx';
    }
}
