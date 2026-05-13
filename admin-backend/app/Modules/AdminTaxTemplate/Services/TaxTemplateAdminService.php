<?php

namespace App\Modules\AdminTaxTemplate\Services;

use App\TaxComponentTemplate;
use App\TaxDetailTemplate;
use App\TaxMasterTemplate;
use App\Modules\AdminTaxTemplate\Support\TaxTemplateComponentNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TaxTemplateAdminService
{
    /**
     * @param  array{country_code?: string, region_code?: string|null}  $filters
     */
    public function list(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $q = TaxMasterTemplate::query()->with(['components.detailRows'])->orderByDesc('template_tax_id');

        if (! empty($filters['country_code'])) {
            $q->where('country_code', strtoupper((string) $filters['country_code']));
        }

        if (array_key_exists('region_code', $filters)) {
            $rc = $filters['region_code'];
            if ($rc === null || $rc === '') {
                $q->where(function ($qq) {
                    $qq->whereNull('region_code')->orWhere('region_code', '');
                });
            } else {
                $q->where('region_code', strtoupper(trim((string) $rc)));
            }
        }

        return $q->paginate(max(1, min(100, $perPage)));
    }

    /** Backward compatibility alias */
    public function listByCountry(?string $countryCode, int $perPage = 50): LengthAwarePaginator
    {
        $f = [];
        if ($countryCode !== null && $countryCode !== '') {
            $f['country_code'] = $countryCode;
        }

        return $this->list($f, $perPage);
    }

    public function getOne(int $id): TaxMasterTemplate
    {
        return TaxMasterTemplate::with(['components.detailRows'])->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): TaxMasterTemplate
    {
        return DB::transaction(function () use ($data) {
            $normComponents = TaxTemplateComponentNormalizer::normalize(is_array($data['components'] ?? null) ? $data['components'] : []);

            $attrs = [
                'country_code' => strtoupper((string) $data['country_code']),
                'region_type' => strtoupper((string) ($data['region_type'] ?? 'COUNTRY')),
                'region_code' => isset($data['region_code']) && trim((string) $data['region_code']) !== ''
                    ? strtoupper(trim((string) $data['region_code']))
                    : null,
                'tax_type' => strtoupper((string) ($data['tax_type'] ?? 'SALES_TAX')),
                'applicability_type' => strtoupper((string) ($data['applicability_type'] ?? 'FLAT')),
                'tax_name' => (string) $data['tax_name'],
                'version' => (int) ($data['version'] ?? 1),
                'is_active' => array_key_exists('is_active', $data) ? (! empty($data['is_active']) ? 1 : 0) : 1,
            ];

            $this->assertNoDuplicateMaster($attrs, null);

            $m = TaxMasterTemplate::create($attrs);

            $this->syncComponents((int) $m->template_tax_id, $normComponents);

            return $m->fresh(['components.detailRows']);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(int $id, array $data): TaxMasterTemplate
    {
        return DB::transaction(function () use ($id, $data) {
            $m = TaxMasterTemplate::query()->findOrFail($id);

            if (isset($data['country_code'])) {
                $m->country_code = strtoupper((string) $data['country_code']);
            }
            if (isset($data['region_type'])) {
                $m->region_type = strtoupper((string) $data['region_type']);
            }
            if (array_key_exists('region_code', $data)) {
                $m->region_code = ($data['region_code'] !== null && trim((string) $data['region_code']) !== '')
                    ? strtoupper(trim((string) $data['region_code']))
                    : null;
            }
            if (isset($data['tax_type'])) {
                $m->tax_type = strtoupper((string) $data['tax_type']);
            }
            if (isset($data['applicability_type'])) {
                $m->applicability_type = strtoupper((string) $data['applicability_type']);
            }
            if (isset($data['tax_name'])) {
                $m->tax_name = (string) $data['tax_name'];
            }
            if (array_key_exists('version', $data)) {
                $m->version = (int) $data['version'];
            }
            if (array_key_exists('is_active', $data)) {
                $m->is_active = ! empty($data['is_active']) ? 1 : 0;
            }

            $this->assertNoDuplicateMaster([
                'country_code' => $m->country_code,
                'region_code' => $m->region_code,
                'tax_name' => $m->tax_name,
            ], $id);

            $m->save();

            if (array_key_exists('components', $data) && is_array($data['components'])) {
                $normComponents = TaxTemplateComponentNormalizer::normalize($data['components']);
                $tcIds = TaxComponentTemplate::where('template_tax_id', $id)->pluck('template_tc_id');
                if ($tcIds->isNotEmpty()) {
                    TaxDetailTemplate::whereIn('template_tc_id', $tcIds)->delete();
                }
                TaxComponentTemplate::where('template_tax_id', $id)->delete();
                $this->syncComponents($id, $normComponents);
            }

            return $m->fresh(['components.detailRows']);
        });
    }

    public function deactivate(int $id): void
    {
        $m = TaxMasterTemplate::query()->findOrFail($id);
        $m->is_active = 0;
        $m->save();
    }

    public function destroy(int $id): void
    {
        $m = TaxMasterTemplate::query()->findOrFail($id);
        DB::transaction(function () use ($m) {
            $tcIds = TaxComponentTemplate::where('template_tax_id', $m->template_tax_id)->pluck('template_tc_id');
            if ($tcIds->isNotEmpty()) {
                TaxDetailTemplate::whereIn('template_tc_id', $tcIds)->delete();
                TaxComponentTemplate::whereIn('template_tc_id', $tcIds)->delete();
            }
            $m->delete();
        });
    }

    /**
     * @param  array{country_code: string, region_code: mixed, tax_name: string}  $attrs
     */
    private function assertNoDuplicateMaster(array $attrs, ?int $excludeId): void
    {
        $q = TaxMasterTemplate::query()
            ->where('country_code', $attrs['country_code'])
            ->where('tax_name', $attrs['tax_name']);

        $rc = $attrs['region_code'] ?? null;
        if ($rc !== null && (string) $rc !== '') {
            $q->where('region_code', strtoupper((string) $rc));
        } else {
            $q->where(function ($qq) {
                $qq->whereNull('region_code')->orWhere('region_code', '');
            });
        }

        if ($excludeId !== null) {
            $q->where('template_tax_id', '!=', $excludeId);
        }

        if ($q->exists()) {
            throw ValidationException::withMessages([
                'tax_name' => ['A template already exists for this country, region, and tax name.'],
            ]);
        }
    }

    /**
     * @param  list<array{component_name: string, details: list<array<string, mixed>>}>  $components
     */
    private function syncComponents(int $templateTaxId, array $components): void
    {
        foreach ($components as $c) {
            $tc = TaxComponentTemplate::create([
                'template_tax_id' => $templateTaxId,
                'component_name' => $c['component_name'],
            ]);
            foreach ($c['details'] as $d) {
                TaxDetailTemplate::create([
                    'template_tc_id' => $tc->template_tc_id,
                    'tax_value' => $d['tax_value'],
                    'tax_start_date' => $d['tax_start_date'],
                    'tax_end_date' => $d['tax_end_date'],
                ]);
            }
        }
    }
}
