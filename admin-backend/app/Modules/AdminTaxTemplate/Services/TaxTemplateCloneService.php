<?php

namespace App\Modules\AdminTaxTemplate\Services;

use App\TaxComponents;
use App\TaxDetail;
use App\TaxMaster;
use App\TaxMasterTemplate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * Copies country tax templates into live tax_master / tax_components / tax_detail for a tenant.
 */
class TaxTemplateCloneService
{
    /**
     * @param  list<int>  $templateTaxIds
     */
    public function cloneTemplatesForCompany(int $companyId, array $templateTaxIds, ?string $countryCode = null): void
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $templateTaxIds), fn ($v) => $v > 0)));
        if ($ids === []) {
            return;
        }

        $query = TaxMasterTemplate::query()
            ->whereIn('template_tax_id', $ids)
            ->where('is_active', 1)
            ->with(['components.detailRows']);

        $cc = $countryCode !== null && $countryCode !== '' ? strtoupper((string) $countryCode) : null;
        if ($cc !== null) {
            $query->where('country_code', $cc);
        }

        $masters = $query->get();
        if ($masters->isEmpty()) {
            return;
        }

        $hasTemplateCol = Schema::hasTable('tax_master') && Schema::hasColumn('tax_master', 'template_tax_id');

        foreach ($masters as $tm) {
            if ($hasTemplateCol && $this->companyHasThisTemplate($companyId, (int) $tm->template_tax_id)) {
                continue;
            }

            DB::transaction(function () use ($companyId, $tm, $hasTemplateCol) {
                $masterAttrs = [
                    'company_id' => $companyId,
                    'tax_name' => $tm->tax_name,
                ];
                if ($hasTemplateCol) {
                    $masterAttrs['template_tax_id'] = (int) $tm->template_tax_id;
                }
                $live = TaxMaster::create($masterAttrs);
                $liveId = (int) $live->getKey();

                foreach ($tm->components as $tc) {
                    $newComp = TaxComponents::create([
                        'tax_id' => $liveId,
                        'component_name' => $tc->component_name,
                    ]);
                    $tcId = (int) ($newComp->tc_id ?? $newComp->getKey());
                    foreach ($tc->detailRows as $d) {
                        TaxDetail::create([
                            'tc_id' => $tcId,
                            'tax_value' => $d->tax_value ?? 0,
                            'tax_start_date' => $d->tax_start_date,
                            'tax_end_date' => $d->tax_end_date,
                        ]);
                    }
                }
            });
        }
    }

    private function companyHasThisTemplate(int $companyId, int $templateTaxId): bool
    {
        return TaxMaster::query()
            ->where('company_id', $companyId)
            ->where('template_tax_id', $templateTaxId)
            ->exists();
    }
}
