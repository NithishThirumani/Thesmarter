<?php

namespace App\Imports\Sheets;

use Session;
use Log;
use App\MerchantCatalogueProducts;
use App\MerchantProductPrices;
use App\MerchantProductTaxes;
use App\MerchantProductTaxInclusive;
use App\MerchantProductRecipe;
use App\MerchantCatalogue;
use App\MetricUnits;
use App\TaxMaster;
use App\UniversalTags;
use App\UniversalTagsMapping;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithStartRow;





class SixthSheetImport implements ToCollection, WithHeadingRow, WithStartRow
{
    private $companyId;
    private $lobId;
    /**
     * @param Collection $collection
     */
    public function headingRow(): int
    {
        return 3;
    }
    public function startRow(): int
    {
        return 4;
    }
    public function collection(Collection $collection)
    {

        if (!empty($collection)) {
            $this->companyId =2;//Session::get('companyId');
            $this->lobId = 4;//Session::get('lobId');
            $catalogue = MerchantCatalogue::updateOrCreate(['company_id' => $this->companyId], ['lob_id'=> $this->lobId]);
            $catalogueId = $catalogue->catalogue_id;

            $products = MerchantCatalogueProducts::where('catalogue_id', $catalogueId)
                ->get();

            $measuringUnits = MetricUnits::all();
            $companyTaxes = TaxMaster::where('company_id', $this->companyId)->get();
            $companyTags = UniversalTags::where('company_id',$this->companyId)->where('module_id',1)->get();
            // print_r(json_encode($productUniversalTag));exit;



            foreach ($collection as $row) {

                // First check if product with same name not available in same company 
                $filterProducts = $products->where('product_name', $row['product_name'])->all();
                if (!empty($filterProducts)) {
                    Log::info("Product " . $row['product_name'] . " already exists");
                    continue;
                }
                $measuringUnitId = NULL;
                if (!empty($row['units'])) {
                    $unit = $measuringUnits->where('unit_abreviation', $row['units'])->all();
                    $measuringUnitId = empty($unit) ? NULL : $row[8];
                }
                $status = 'A';
                if ($row['publish'] == 'N') {
                    $status = 'D';
                }
                $today = \Carbon\Carbon::now()->toDateTimeString();

                // Insert in to merchant catalog 
                $product= MerchantCatalogueProducts::create([
                    'catalogue_id' => $catalogueId,
                    'product_type' => 'N',
                    'product_logo' => NULL,
                    'product_name' => $row['product_name'],
                    'product_brand' => $row['brand'],
                    'product_code' => $row['product_code'],
                    'product_hsn_code' => $row['hsn_code'],
                    'product_barcode' =>$row['barcode'],
                    'product_qr_code' => NULL,
                    'quantity_based_price_flag' => 'N',
                    'product_service_charge_flag' => 'N',
                    'product_discount_flag' => $row['include_in_discount'],
                    'product_count_stock' => $row['inventory_tracker'],
                    'measuring_unit_id' => $measuringUnitId,
                    'product_status' => $status
                ]);
                $productId = $product->product_id;
                // Insert in to product pricing 
                if (!empty($row['price'])) {
                    $mppId = MerchantProductPrices::create([
                        'product_id' => $productId,
                        'product_cost_price' => (float) $row['price'],
                        'product_sell_price' => (float) $row['price'],
                        'start_dtm' => $today,
                        'end_dtm' => NULL,
                        'price_status' => 'A',
                    ]);
                }
                // Insert into product taxes  
                if (!empty($row['tax'])) {
                    $taxDetails = $companyTaxes->where('tax_name', $row['tax'])->first();
                    $taxId = $taxDetails->tax_id;
                    MerchantProductTaxes::firstOrCreate([
                        'product_id' => $productId,
                        'tax_id' => $taxId,
                        'status' => 'A',
                    ]);
                }
                // Insert into product recipes
                // Currrently this option is not avaible in the sheet

                // // Insert product tax inclusive
                if (!empty($row['tax_incl_flag']) && $row['tax_incl_flag'] == 'Y') {
                    MerchantProductTaxInclusive::create([
                        'product_id' => $productId,
                        'inclusive_flag' => 'Y',
                        'start_date_time' => $today,
                        'end_date_time' => NULL,
                        'status' => 'A'
                    ]);
                }
                // Insert into product tags mapping 
                if (!empty($row['tag'])) {
                    $tagDetails = $companyTags->where('tag_name', $row['tag'])->first();
                    $tagId = $tagDetails->tag_id;
                    UniversalTagsMapping::firstOrCreate([
                        'resource_id' => $productId,
                        'tag_id' => $tagId,
                        'resource_module_id' => 1
                    ]);
                }
                // if (!empty($row['tag'])) {
                //     $tags = explode("|", $row['tag']);
                //     foreach ($tags as $tag) {
                //         $filterTag = UniversalTags::where('company_id', $this->companyId)
                //             ->where('tag_name', $tag)->first();
                //         if (!$filterTag) {
                //             $tagDetails = array(
                //                 'company_id' => $this->companyId,
                //                 'module_id' => 1,
                //                 'tag_name' => $tag,
                //                 'created_by' => 1
                //             );

                //             $filterTag = UniversalTags::create($tagDetails);
                //         }
                //         $tagId = $filterTag->tag_id;
                //         UniversalTagsMapping::create([
                //             'tag_id' => $tagId,
                //             'resource_id' => $productId,
                //             'resource_module_id' => 1
                //         ]);
                //     }
                // }
            }
        }
    }
}
