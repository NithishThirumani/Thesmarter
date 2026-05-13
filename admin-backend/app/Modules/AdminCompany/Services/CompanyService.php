<?php

namespace App\Modules\AdminCompany\Services;

use App\CompanyDetail;
use App\BranchDetail;
use App\ContactDetail;
use App\CompanyPayment;
use App\CompanyBusinessHour;
use App\CompanySetting;
use App\PaymentMethods;
use App\TaxMaster;
use App\TaxComponents;
use App\TaxDetail;
use App\CompanyFeatures;
use App\TaxMasterTemplate;
use App\Mail\CompanyTenantWelcomeMail;
use App\Modules\AdminAuth\Services\AdminAuthService;
use App\Modules\AdminTaxTemplate\Services\TaxTemplateCloneService;
use App\Support\Mail\PlatformMailConfigurator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Crypt;
use RuntimeException;

/**
 * Atomic company (tenant) creation and CRUD. All create operations run in a single transaction.
 */
class CompanyService
{
    private AdminAuthService $authService;

    private TaxTemplateCloneService $taxTemplateCloneService;

    public function __construct(AdminAuthService $authService, TaxTemplateCloneService $taxTemplateCloneService)
    {
        $this->authService = $authService;
        $this->taxTemplateCloneService = $taxTemplateCloneService;
    }

    private function hasBusinessHoursTable(): bool
    {
        return Schema::hasTable('company_business_hours');
    }

    private function hasSettingsTable(): bool
    {
        return Schema::hasTable('company_settings');
    }

    private function companyRelations(): array
    {
        $relations = ['branches.contact', 'country', 'lineOfBusiness'];
        if ($this->hasBusinessHoursTable()) {
            $relations[] = 'businessHours';
        }
        if ($this->hasSettingsTable()) {
            $relations[] = 'settings';
        }
        return $relations;
    }

    /**
     * Verify admin PIN then create company with all related records in one transaction.
     *
     * @param array $payload Keys: company, branches, contacts, payment_method_ids, payment_providers, taxes, feature_ids, business_hours, settings
     * @param string $adminEmail Current admin email (from JWT)
     * @param string $pin Admin PIN for confirmation
     * @return CompanyDetail
     */
    public function createCompany(array $payload, string $adminEmail, string $pin): CompanyDetail
    {
        $this->authService->verifyPin($adminEmail, $pin);

        $company = DB::transaction(function () use ($payload) {
            $company = $this->createCompanyRecord($payload['company'] ?? []);
            $companyPhone = trim((string) (($payload['company'] ?? [])['phone_number'] ?? ''));
            $contactIds = $this->createContacts($company->company_id, $payload['contacts'] ?? [], $companyPhone);
            $this->createBranches($company->company_id, $payload['branches'] ?? [], $contactIds);
            $this->syncBusinessHours($company->company_id, $payload['business_hours'] ?? [], $payload['company'] ?? []);
            $this->upsertSettings($company->company_id, $payload['settings'] ?? []);
            $this->attachPaymentMethods(
                $company->company_id,
                $payload['payment_method_ids'] ?? [],
                $payload['payment_providers'] ?? []
            );
            $templateIds = $this->resolveTemplateTaxIdsFromPayload($payload);
            if ($templateIds !== []) {
                $country = isset($payload['country_code']) ? strtoupper(trim((string) $payload['country_code'])) : '';
                $this->taxTemplateCloneService->cloneTemplatesForCompany(
                    (int) $company->company_id,
                    $templateIds,
                    $country !== '' ? $country : null
                );
            }
            $this->createTaxRecords($company->company_id, $payload['taxes'] ?? []);
            $this->attachFeatures($company->company_id, $payload['feature_ids'] ?? []);

            return $company->fresh($this->companyRelations());
        });

        $toEmail = trim((string) ($company->email ?? ''));
        if ($toEmail !== '') {
            try {
                PlatformMailConfigurator::apply();
                $mailManager = app('mail.manager');
                if (method_exists($mailManager, 'purge')) {
                    $mailManager->purge();
                }
                Mail::to($toEmail)->send(
                    new CompanyTenantWelcomeMail(
                        (string) ($company->company_name ?? 'your organization'),
                        isset($company->legal_name) ? (string) $company->legal_name : null
                    )
                );
                Log::info('company.welcome_mail_sent', ['company_id' => $company->company_id]);
            } catch (\Throwable $e) {
                Log::warning('company.welcome_mail_failed', [
                    'company_id' => $company->company_id,
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $company;
    }

    /**
     * Generate unique company_code: CMP-<timestamp>-<random>
     */
    public function generateCompanyCode(): string
    {
        $maxAttempts = 10;
        for ($i = 0; $i < $maxAttempts; $i++) {
            $code = 'CMP-' . time() . '-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            if (!CompanyDetail::where('company_code', $code)->exists()) {
                return $code;
            }
        }
        throw new RuntimeException('Could not generate unique company code.');
    }

    private function createCompanyRecord(array $data): CompanyDetail
    {
        $company = new CompanyDetail();
        $company->company_name = $data['company_name'] ?? 'New Company';
        $company->legal_name = $data['legal_name'] ?? $company->company_name;
        $rawStatus = $data['status'] ?? $data['company_status'] ?? 1;
        $company->company_status = $this->persistableCompanyStatus($rawStatus);
        if (Schema::hasColumn($company->getTable(), 'company_code')) {
            $company->company_code = $this->generateCompanyCode();
        }
        foreach ($company->getFillable() as $key) {
            if ($key !== 'company_name' && $key !== 'company_status' && array_key_exists($key, $data)) {
                $company->$key = $data[$key];
            }
        }
        if (array_key_exists('email', $data)) {
            $company->email = $data['email'];
        }
        // appointment_auto_confirm only valid when customer_app is on
        if (!$company->customer_app) {
            $company->appointment_auto_confirm = false;
        }
        $company->save();
        return $company;
    }

    private function createContacts(int $companyId, array $contacts, string $companyPhoneFallback = ''): array
    {
        // Your intended relationship is: company -> branch_detail -> contact_detail.
        // Some environments/migrations may not have `company_id` on `contact_detail`.
        // So we only write `company_id` when the column exists.
        $contactTable = (new ContactDetail())->getTable();
        $hasCompanyIdColumn = Schema::hasColumn($contactTable, 'company_id');
        $fallback = trim($companyPhoneFallback);

        $ids = [];
        foreach ($contacts as $idx => $c) {
            $rawPhone = trim((string) ($c['phone'] ?? ''));
            if ($rawPhone === '' && $idx === 0 && $fallback !== '') {
                $rawPhone = $fallback;
            }

            $contact = ContactDetail::create(array_merge(
                array_filter([
                    'company_id' => $hasCompanyIdColumn ? $companyId : null,
                    'phone' => $rawPhone !== '' ? $rawPhone : null,
                    'email' => $c['email'] ?? null,
                    'pincode' => $c['pincode'] ?? null,
                    'city' => $c['city'] ?? null,
                    'state' => $c['state'] ?? null,
                    'country' => $c['country'] ?? null,
                    'address1' => $c['address1'] ?? null,
                    'area' => $c['area'] ?? null,
                    'longitude' => $c['longitude'] ?? null,
                    'latitude' => $c['latitude'] ?? null,
                ])
            ));
            $ids[] = $contact->contact_id;
        }
        return $ids;
    }

    private function createBranches(int $companyId, array $branches, array $contactIds): void
    {
        
        foreach ($branches as $i => $b) {
            $contactId = $contactIds[$i] ?? ($contactIds[0] ?? null);
            $branchType = $b['branch_type'] ?? 'normal';
            if($branchType == 'normal'){
                $b['work_type'] = 1; // noraml
            }else if($branchType == 'warehouse'){
                $b['work_type'] = 2; // warehouse
            }else if($branchType == 'retail'){
                $b['work_type'] = 3; // retail
            }
            if (!empty($b['head_branch'])) {
                $b['branch_head']   = 'H'; //  true
            } else {
                $b['branch_head'] = 'S'; // false
            }
            BranchDetail::create([
                'company_id' => $companyId,
                'branch_name' => $b['branch_name'] ?? null,
                'latitude' => $b['latitude'] ?? null,
                'longitude' => $b['longitude'] ?? null,
                'branch_status' => $b['branch_status'] ?? 1,
                'contact_id' => $contactId,
                'branch_type' => $b['branch_head'] ?? 'S',
                'work_type' => $b['work_type'] ?? 1,
            ]);
        }
    }

    private function attachPaymentMethods(int $companyId, array $paymentMethodIds, array $paymentProviders = []): void
    {
        $paymentKeyColumn = Schema::hasColumn('company_payments', 'payment_method_id') ? 'payment_method_id' : 'payment_id';
        $providerByPaymentId = $this->normalizeProviderPayload($paymentProviders);

        foreach (array_unique($paymentMethodIds) as $pid) {
            $pid = (int) $pid;
            if ($pid <= 0) {
                continue;
            }
            $providerPayload = $providerByPaymentId[$pid] ?? [];
            $providerName = PaymentMethods::where('payment_id', $pid)->value('payment_name');
            $requiresSecret = $this->isCustomProvider($providerName);

            $payload = [
                'company_id' => $companyId,
                $paymentKeyColumn => $pid,
                'merchant_id' => $providerPayload['merchant_id'] ?? null,
            ];

            if (!empty($providerPayload['secret_key'])) {
                $payload['secret_key_encrypted'] = Crypt::encryptString((string) $providerPayload['secret_key']);
            } elseif ($requiresSecret) {
                $payload['secret_key_encrypted'] = null;
            }

            CompanyPayment::firstOrCreate(
                ['company_id' => $companyId, $paymentKeyColumn => $pid],
                $payload
            );
        }
    }

    private function createTaxRecords(int $companyId, array $taxes): void
    {
        foreach ($taxes as $t) {
            $tax = TaxMaster::create([
                'company_id' => $companyId,
                'tax_name' => $t['tax_name'] ?? 'Tax',
            ]);
            foreach ($t['components'] ?? [] as $comp) {
                $tc = TaxComponents::create([
                    'tax_id' => $tax->getKey(),
                    'component_name' => $comp['component_name'] ?? 'Component',
                ]);
                $tcId = $tc->tc_id ?? $tc->id;
                foreach ($comp['details'] ?? [] as $d) {
                    TaxDetail::create([
                        'tc_id' => $tcId,
                        'tax_value' => $d['tax_value'] ?? 0,
                        'tax_start_date' => $d['tax_start_date'] ?? now()->toDateString(),
                        'tax_end_date' => $d['tax_end_date'] ?? null,
                    ]);
                }
            }
        }
    }

    private function attachFeatures(int $companyId, array $featureIds): void
    {
        foreach (array_unique($featureIds) as $fid) {
            CompanyFeatures::firstOrCreate(
                ['company_id' => $companyId, 'feature_id' => (int) $fid],
                ['company_id' => $companyId, 'feature_id' => (int) $fid, 'company_feature_status' => 1]
            );
        }
    }

    /**
     * Apply country tax templates to an existing tenant (additive; skips already-cloned templates when trace column exists).
     *
     * @param  array{country_code?:string,selected_template_ids?:list<int|string>,apply_all_country_tax_templates?:bool}  $payload
     */
    public function applyTaxTemplatesFromCountrySelection(int $companyId, array $payload): void
    {
        if (! Schema::hasTable('tax_master_template')) {
            return;
        }
        $ids = $this->resolveTemplateTaxIdsFromPayload($payload);
        if ($ids === []) {
            return;
        }
        $country = isset($payload['country_code']) ? strtoupper(trim((string) $payload['country_code'])) : '';

        $this->taxTemplateCloneService->cloneTemplatesForCompany((int) $companyId, $ids, $country !== '' ? $country : null);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return list<int>
     */
    private function resolveTemplateTaxIdsFromPayload(array $payload): array
    {
        if (! Schema::hasTable('tax_master_template')) {
            return [];
        }

        if (! empty($payload['apply_all_country_tax_templates']) && ! empty($payload['country_code'])) {
            return TaxMasterTemplate::forCountry(strtoupper((string) $payload['country_code']))
                ->orderBy('tax_name')
                ->pluck('template_tax_id')
                ->map(static fn ($v) => (int) $v)
                ->all();
        }

        return array_values(array_unique(array_filter(
            array_map('intval', (array) ($payload['selected_template_ids'] ?? [])),
            fn ($v) => $v > 0
        )));
    }

    private function normalizeProviderPayload(array $paymentProviders): array
    {
        $out = [];
        foreach ($paymentProviders as $row) {
            $paymentId = isset($row['payment_id']) ? (int) $row['payment_id'] : 0;
            if ($paymentId <= 0) {
                continue;
            }
            $out[$paymentId] = [
                'merchant_id' => $row['merchant_id'] ?? null,
                'secret_key' => $row['secret_key'] ?? null,
            ];
        }
        return $out;
    }

    private function isCustomProvider(?string $providerName): bool
    {
        $name = strtolower(trim((string) $providerName));
        if ($name === '') {
            return false;
        }
        return str_contains($name, 'stripe')
            || str_contains($name, 'razorpay')
            || str_contains($name, 'custom');
    }

    public function syncBusinessHours(int $companyId, array $businessHours, array $companyPayload = []): void
    {
        if (!$this->hasBusinessHoursTable()) {
            return;
        }

        $companyId = (int) $companyId;
        $rows = [];
        if (!empty($businessHours)) {
            foreach ($businessHours as $row) {
                $rows[] = [
                    'day_of_week' => $row['day_of_week'] ?? 'Monday',
                    'is_open' => !empty($row['is_open']),
                    'opening_time' => $row['opening_time'] ?? null,
                    'closing_time' => $row['closing_time'] ?? null,
                    'slot_index' => (int) ($row['slot_index'] ?? 1),
                ];
            }
        } else {
            $dawn = $companyPayload['company_dawn'] ?? null;
            $dusk = $companyPayload['company_dusk'] ?? null;
            if (!empty($dawn) || !empty($dusk)) {
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                foreach ($days as $day) {
                    $rows[] = [
                        'day_of_week' => $day,
                        'is_open' => !empty($dawn) && !empty($dusk),
                        'opening_time' => $dawn,
                        'closing_time' => $dusk,
                        'slot_index' => 1,
                    ];
                }
            }
        }

        if (empty($rows)) {
            return;
        }

        DB::transaction(function () use ($companyId, $rows) {
            CompanyBusinessHour::where('company_id', $companyId)->delete();
            foreach ($rows as $row) {
                CompanyBusinessHour::create([
                    'company_id' => $companyId,
                    'day_of_week' => $row['day_of_week'],
                    'is_open' => $row['is_open'],
                    'opening_time' => $row['is_open'] ? $row['opening_time'] : null,
                    'closing_time' => $row['is_open'] ? $row['closing_time'] : null,
                    'slot_index' => max(1, (int) ($row['slot_index'] ?? 1)),
                ]);
            }
        });
    }

    public function upsertSettings(int $companyId, array $settings): void
    {
        if (!$this->hasSettingsTable()) {
            return;
        }

        if (empty($settings)) {
            return;
        }

        $defaults = [
            'enforce_2fa' => false,
            'geo_location_tracking' => false,
            'geo_fencing_enabled' => false,
            'geo_fencing_radius' => null,
            'appointment_time_slice_enabled' => false,
            'appointment_time_slice_minutes' => null,
            'auto_approve_appointments' => false,
            'marketing_message' => null,
            'public_company_page' => false,
        ];

        $payload = array_intersect_key(array_merge($defaults, $settings), $defaults);
        if (empty($payload['geo_fencing_enabled'])) {
            $payload['geo_fencing_radius'] = null;
        }
        if (empty($payload['appointment_time_slice_enabled'])) {
            $payload['appointment_time_slice_minutes'] = null;
        }

        CompanySetting::updateOrCreate(
            ['company_id' => (int) $companyId],
            $payload
        );
    }

    public function list(array $filters = [], int $perPage = 15)
    {
        $q = CompanyDetail::query()->with($this->companyRelations());
        if (!empty($filters['search'])) {
            $s = '%' . $filters['search'] . '%';
            $q->where(function ($q) use ($s) {
                $q->where('company_name', 'like', $s);
                if (\Schema::hasColumn('company_detail', 'legal_name')) {
                    $q->orWhere('legal_name', 'like', $s);
                }
                if (\Schema::hasColumn('company_detail', 'email')) {
                    $q->orWhere('email', 'like', $s);
                }
                if (\Schema::hasColumn('company_detail', 'company_code')) {
                    $q->orWhere('company_code', 'like', $s);
                }
            });
        }

        $status = $filters['status'] ?? null;
        if ($status !== null && $status !== '' && $status !== 'all') {
            $this->applyCompanyStatusFilter($q, $status);
        }

        $lobId = $filters['company_business_id'] ?? null;
        if ($lobId !== null && $lobId !== '' && (int) $lobId > 0 && Schema::hasColumn('company_detail', 'company_business_id')) {
            $q->where('company_business_id', (int) $lobId);
        }

        $sort = is_string($filters['sort'] ?? null) ? strtolower(trim((string) $filters['sort'])) : '';
        $table = (new CompanyDetail())->getTable();
        $createdCol = Schema::hasColumn($table, 'create_dtm') ? 'create_dtm' : 'company_id';
        switch ($sort) {
            case 'created_asc':
                $q->orderBy($createdCol, 'asc')->orderBy('company_id', 'asc');
                break;
            case 'name_asc':
                $q->orderBy('company_name', 'asc')->orderBy('company_id', 'desc');
                break;
            case 'name_desc':
                $q->orderBy('company_name', 'desc')->orderBy('company_id', 'desc');
                break;
            case 'created_desc':
            default:
                $q->orderBy($createdCol, 'desc')->orderBy('company_id', 'desc');
                break;
        }

        return $q->paginate($perPage);
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder  $q
     */
    private function applyCompanyStatusFilter($q, $status): void
    {
        $s = is_string($status) ? strtolower(trim($status)) : $status;

        if ($s === 'active' || $s === 1 || $s === '1') {
            $q->where(function ($w) {
                $w->where('company_status', 1)
                    ->orWhere('company_status', '1')
                    ->orWhere('company_status', 'A')
                    ->orWhere('company_status', 'Active');
            });

            return;
        }

        if ($s === 'inactive' || $s === 0 || $s === '0') {
            $q->where(function ($w) {
                $w->where(function ($inner) {
                    $inner->whereNotNull('company_status')
                        ->whereNotIn('company_status', [1, '1', 'A', 'Active']);
                })->orWhereNull('company_status');
            });

            return;
        }

        $q->where('company_status', $status);
    }

    public function getOne(int $companyId): CompanyDetail
    {
        $company = CompanyDetail::with($this->companyRelations())->findOrFail($companyId);

        $company->load(['companyPayments.paymentMethod', 'companyFeatures.feature', 'taxMasters.components.details']);
        $company->setAttribute('company_logo_urls', CompanyLogoStorageService::publicUrls($company->company_logo));

        return $company;
    }

    public function update(int $companyId, array $data): CompanyDetail
    {
        if (array_key_exists('company_status', $data)) {
            $data['company_status'] = $this->persistableCompanyStatus($data['company_status']);
        }

        $company = CompanyDetail::findOrFail($companyId);
        $company->update(array_intersect_key($data, array_flip($company->getFillable())));

        if (array_key_exists('business_hours', $data) && is_array($data['business_hours'])) {
            $this->syncBusinessHours($companyId, $data['business_hours'], $data);
        } elseif (array_key_exists('company_dawn', $data) || array_key_exists('company_dusk', $data)) {
            $this->syncBusinessHours($companyId, [], $data);
        }

        if (array_key_exists('settings', $data) && is_array($data['settings'])) {
            $this->upsertSettings($companyId, $data['settings']);
        }

        $company = $company->fresh($this->companyRelations());
        $company->load(['companyPayments.paymentMethod', 'companyFeatures.feature', 'taxMasters.components.details']);
        $company->setAttribute('company_logo_urls', CompanyLogoStorageService::publicUrls($company->company_logo));

        return $company;
    }

    public function delete(int $companyId): void
    {
        $company = CompanyDetail::findOrFail($companyId);
        $logoKey = $company->company_logo;
        $company->delete();
        if ($logoKey) {
            app(CompanyLogoStorageService::class)->deleteByKey($logoKey);
        }
    }

    /**
     * Replace branches and contacts (wizard-shaped payload). Uses branch_id/contact_id when present.
     */
    public function syncBranchesAndContacts(int $companyId, array $branches, array $contacts): void
    {
        $companyId = (int) $companyId;
        $contactTable = (new ContactDetail())->getTable();
        $hasCompanyIdColumn = Schema::hasColumn($contactTable, 'company_id');

        DB::transaction(function () use ($companyId, $branches, $contacts, $hasCompanyIdColumn) {
            $payloadBranchIds = [];
            foreach ($branches as $b) {
                if (!empty($b['branch_id'])) {
                    $payloadBranchIds[] = (int) $b['branch_id'];
                }
            }

            $existing = BranchDetail::where('company_id', $companyId)->get();
            foreach ($existing as $eb) {
                if (!in_array((int) $eb->branch_id, $payloadBranchIds, true)) {
                    $cid = $eb->contact_id;
                    $eb->delete();
                    if ($cid) {
                        ContactDetail::where('contact_id', $cid)->delete();
                    }
                }
            }

            foreach ($branches as $i => $b) {
                $c = $contacts[$i] ?? [];
                $workType = 1;
                $bt = $b['branch_type'] ?? 'normal';
                if ($bt === 'warehouse') {
                    $workType = 2;
                } elseif ($bt === 'retail') {
                    $workType = 3;
                }
                $branchHeadLetter = !empty($b['head_branch']) ? 'H' : 'S';

                $contactPayload = array_merge(
                    array_filter([
                        'company_id' => $hasCompanyIdColumn ? $companyId : null,
                        'phone' => $c['phone'] ?? null,
                        'email' => $c['email'] ?? null,
                        'pincode' => $c['pincode'] ?? null,
                        'city' => $c['city'] ?? null,
                        'state' => $c['state'] ?? null,
                        'country' => $c['country'] ?? null,
                        'address1' => $c['address1'] ?? null,
                        'area' => $c['area'] ?? null,
                        'longitude' => $c['longitude'] ?? null,
                        'latitude' => $c['latitude'] ?? null,
                    ])
                );

                $branchId = isset($b['branch_id']) ? (int) $b['branch_id'] : null;

                if ($branchId && BranchDetail::where('company_id', $companyId)->where('branch_id', $branchId)->exists()) {
                    $br = BranchDetail::where('branch_id', $branchId)->first();
                    ContactDetail::where('contact_id', $br->contact_id)->update($contactPayload);
                    $br->update([
                        'branch_name' => $b['branch_name'] ?? null,
                        'latitude' => $b['latitude'] ?? null,
                        'longitude' => $b['longitude'] ?? null,
                        'branch_status' => $b['branch_status'] ?? 1,
                        'branch_type' => $branchHeadLetter,
                        'work_type' => $workType,
                    ]);
                } else {
                    $contact = ContactDetail::create(array_merge(
                        array_filter([
                            'company_id' => $hasCompanyIdColumn ? $companyId : null,
                            'phone' => $c['phone'] ?? null,
                            'email' => $c['email'] ?? null,
                            'pincode' => $c['pincode'] ?? null,
                            'city' => $c['city'] ?? null,
                            'state' => $c['state'] ?? null,
                            'country' => $c['country'] ?? null,
                            'address1' => $c['address1'] ?? null,
                            'area' => $c['area'] ?? null,
                            'longitude' => $c['longitude'] ?? null,
                            'latitude' => $c['latitude'] ?? null,
                        ])
                    ));
                    BranchDetail::create([
                        'company_id' => $companyId,
                        'branch_name' => $b['branch_name'] ?? null,
                        'latitude' => $b['latitude'] ?? null,
                        'longitude' => $b['longitude'] ?? null,
                        'branch_status' => $b['branch_status'] ?? 1,
                        'contact_id' => $contact->contact_id,
                        'branch_type' => $branchHeadLetter,
                        'work_type' => $workType,
                    ]);
                }
            }
        });
    }

    public function syncPaymentMethods(int $companyId, array $paymentMethodIds, array $paymentProviders = []): void
    {
        $companyId = (int) $companyId;
        $ids = array_unique(array_map('intval', $paymentMethodIds));
        $paymentKeyColumn = Schema::hasColumn('company_payments', 'payment_method_id') ? 'payment_method_id' : 'payment_id';
        $providerByPaymentId = $this->normalizeProviderPayload($paymentProviders);
        DB::transaction(function () use ($companyId, $ids) {
            CompanyPayment::where('company_id', $companyId)->delete();
        });

        DB::transaction(function () use ($companyId, $ids, $paymentKeyColumn, $providerByPaymentId) {
            foreach ($ids as $pid) {
                if ($pid <= 0) {
                    continue;
                }
                $providerPayload = $providerByPaymentId[$pid] ?? [];
                $providerName = PaymentMethods::where('payment_id', $pid)->value('payment_name');
                $row = [
                    'company_id' => $companyId,
                    $paymentKeyColumn => $pid,
                    'merchant_id' => $providerPayload['merchant_id'] ?? null,
                ];
                if (!empty($providerPayload['secret_key'])) {
                    $row['secret_key_encrypted'] = Crypt::encryptString((string) $providerPayload['secret_key']);
                } elseif ($this->isCustomProvider($providerName)) {
                    $row['secret_key_encrypted'] = null;
                }
                CompanyPayment::create($row);
            }
        });
    }

    public function syncFeatures(int $companyId, array $featureIds): void
    {
        $companyId = (int) $companyId;
        $ids = array_unique(array_map('intval', $featureIds));
        DB::transaction(function () use ($companyId, $ids) {
            CompanyFeatures::where('company_id', $companyId)->delete();
            foreach ($ids as $fid) {
                if ($fid <= 0) {
                    continue;
                }
                CompanyFeatures::create([
                    'company_id' => $companyId,
                    'feature_id' => $fid,
                    'company_feature_status' => 1,
                ]);
            }
        });
    }

    public function syncTaxes(int $companyId, array $taxes): void
    {
        $companyId = (int) $companyId;
        DB::transaction(function () use ($companyId, $taxes) {
            $existing = TaxMaster::where('company_id', $companyId)->with('components.details')->get();
            foreach ($existing as $tm) {
                foreach ($tm->components as $comp) {
                    foreach ($comp->details as $d) {
                        $d->delete();
                    }
                    $comp->delete();
                }
                $tm->delete();
            }
            foreach ($taxes as $t) {
                $tax = TaxMaster::create([
                    'company_id' => $companyId,
                    'tax_name' => $t['tax_name'] ?? 'Tax',
                ]);
                foreach ($t['components'] ?? [] as $comp) {
                    $tc = TaxComponents::create([
                        'tax_id' => $tax->getKey(),
                        'component_name' => $comp['component_name'] ?? 'Component',
                    ]);
                    $tcId = $tc->tc_id ?? $tc->id;
                    foreach ($comp['details'] ?? [] as $d) {
                        TaxDetail::create([
                            'tc_id' => $tcId,
                            'tax_value' => $d['tax_value'] ?? 0,
                            'tax_start_date' => $d['tax_start_date'] ?? now()->toDateString(),
                            'tax_end_date' => $d['tax_end_date'] ?? null,
                        ]);
                    }
                }
            }
        });
    }

    /**
     * Map UI / API status (0/1, booleans) to the value the DB column expects (ENUM 'A'/'D', tinyint, etc.).
     */
    private function persistableCompanyStatus($incoming)
    {
        $wantActive = $this->isIncomingCompanyStatusActive($incoming);

        $type = $this->getCompanyStatusColumnType();
        if ($type === null || $type === '') {
            return $wantActive ? 1 : 0;
        }

        $typeLower = strtolower($type);
        if (strpos($typeLower, 'enum(') === 0) {
            $members = $this->parseMysqlEnumMembers($type);
            if ($members === []) {
                return $wantActive ? 1 : 0;
            }
            if ($wantActive) {
                foreach ($members as $mem) {
                    $u = strtoupper((string) $mem);
                    if (in_array($u, ['A', 'ACTIVE', '1', 'Y', 'YES', 'TRUE', 'ENABLED'], true)) {
                        return $mem;
                    }
                }

                return $members[0];
            }
            foreach ($members as $mem) {
                $u = strtoupper((string) $mem);
                if (in_array($u, ['D', 'I', 'INACTIVE', '0', 'N', 'NO', 'FALSE', 'DISABLED'], true)) {
                    return $mem;
                }
            }

            return count($members) >= 2 ? $members[1] : $members[0];
        }

        if (strpos($typeLower, 'int') !== false || $typeLower === 'bit(1)') {
            return $wantActive ? 1 : 0;
        }

        return $wantActive ? 'A' : 'D';
    }

    private function isIncomingCompanyStatusActive($incoming): bool
    {
        if ($incoming === null || $incoming === '') {
            return true;
        }
        if (is_bool($incoming)) {
            return $incoming;
        }
        if (is_numeric($incoming)) {
            return (int) $incoming === 1;
        }
        $s = strtoupper(trim((string) $incoming));

        return in_array($s, ['A', 'ACTIVE', '1', 'Y', 'YES', 'TRUE', 'ENABLED'], true);
    }

    private function getCompanyStatusColumnType(): ?string
    {
        static $cached = false;
        static $type = null;
        if ($cached) {
            return $type;
        }
        $cached = true;
        try {
            if (! Schema::hasTable('company_detail') || ! Schema::hasColumn('company_detail', 'company_status')) {
                return $type;
            }
            $row = DB::selectOne('SHOW COLUMNS FROM `company_detail` WHERE Field = ?', ['company_status']);
            $type = ($row && isset($row->Type)) ? (string) $row->Type : null;
        } catch (\Throwable $e) {
            $type = null;
        }

        return $type;
    }

    /**
     * @return list<string>
     */
    private function parseMysqlEnumMembers(string $enumType): array
    {
        if (! preg_match('/^enum\s*\((.*)\)\s*$/i', $enumType, $m)) {
            return [];
        }
        if (! preg_match_all("/'((?:[^'\\\\]|\\\\.)*)'/", $m[1], $mm)) {
            return [];
        }

        return $mm[1];
    }
}
