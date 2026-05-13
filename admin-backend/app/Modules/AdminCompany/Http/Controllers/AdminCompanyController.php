<?php

namespace App\Modules\AdminCompany\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\AdminCompany\Services\CompanyService;
use App\Modules\AdminAuth\Models\AdminUser;
use App\PaymentMethods;
use App\AppFeatures;
use App\TaxMaster;
use App\Country;
use App\Modules\AdminCompany\Services\CompanyLogoStorageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use App\TaxMasterTemplate;

/**
 * Company (tenant) CRUD under /admin/company/*. All routes require adminAuth.
 */
class AdminCompanyController extends Controller
{
    private CompanyService $companyService;

    private CompanyLogoStorageService $logoStorage;

    public function __construct(CompanyService $companyService, CompanyLogoStorageService $logoStorage)
    {
        $this->companyService = $companyService;
        $this->logoStorage = $logoStorage;
    }

    /**
     * GET /admin/company — list with pagination and search/filter.
     */
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $perPage = min(max($perPage, 1), 100);
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
            'company_business_id' => $request->input('company_business_id'),
            'sort' => $request->input('sort'),
        ];
        $list = $this->companyService->list($filters, $perPage);
        $items = $list->items();
        foreach ($items as $row) {
            $row->setAttribute('company_logo_urls', CompanyLogoStorageService::publicUrls($row->company_logo));
        }
        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'current_page' => $list->currentPage(),
                'last_page' => $list->lastPage(),
                'per_page' => $list->perPage(),
                'total' => $list->total(),
            ],
        ], 200);
    }

    /**
     * GET /admin/company/{id} — single company with branches, contacts, payments, tax, features.
     */
    public function show(int $id): JsonResponse
    {
        try {
            $company = $this->companyService->getOne($id);
            return response()->json(['success' => true, 'data' => $company], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Company not found.'], 404);
        }
    }

    /**
     * POST /admin/company — create company (atomic). Body must include pin for confirmation.
     */
    public function store(Request $request): JsonResponse
    {
        $companyInput = (array) $request->input('company', []);
        $emailRaw = isset($companyInput['email']) ? trim((string) $companyInput['email']) : '';
        $companyInput['email'] = $emailRaw !== '' ? $emailRaw : null;
        $websiteRaw = isset($companyInput['company_website']) ? (string) $companyInput['company_website'] : '';
        $companyInput['company_website'] = $this->normalizeCompanyWebsiteInput($websiteRaw);
        $request->merge(['company' => $companyInput]);

        $v = Validator::make($request->all(), [
            'pin' => 'required|string|min:4|max:12',
            'company' => 'required|array',
            'company.company_name' => 'required|string|max:255',
            'company.legal_name' => 'required|string|max:255',
            'company.tag_line' => 'nullable|string|max:255',
            'company.description' => 'nullable|string',
            'company.phone_number' => ['required', 'string', 'max:32', 'regex:/^\+?[0-9\-\s\(\)]{7,32}$/'],
            'company.email' => ['nullable', 'email', 'max:255', Rule::unique('company_detail', 'email')],
            'company.company_business_id' => 'required|integer',
            'company.company_website' => 'nullable|url|max:255',
            'company.company_dawn' => 'required|string|max:20',
            'company.company_dusk' => 'required|string|max:20',
            'company.status' => 'sometimes|integer',
            'company.company_logo' => 'nullable|string|max:64',
            'company.customer_app' => 'sometimes|boolean',
            'company.appointment_auto_confirm' => 'sometimes|boolean',
            'company.bank_name' => 'nullable|string|max:255',
            'company.bank_code' => 'nullable|string|max:64',
            'company.account_name' => 'nullable|string|max:255',
            'company.account_number' => 'nullable|string|max:64',
            'branches' => 'sometimes|array',
            'branches.*.branch_name' => 'required_with:branches|string|max:255',
            'branches.*.latitude' => 'nullable|numeric|between:-90,90',
            'branches.*.longitude' => 'nullable|numeric|between:-180,180',
            'branches.*.branch_status' => 'sometimes|integer',
            'branches.*.branch_type' => 'nullable|string',
            'branches.*.work_type' => 'nullable|string',
            'contacts' => 'sometimes|array',
            'contacts.*.phone' => 'nullable|string',
            'contacts.*.email' => 'nullable|email',
            'contacts.*.address1' => 'nullable|string',
            'contacts.*.city' => 'nullable|string',
            'contacts.*.state' => 'nullable|string',
            'contacts.*.country' => 'nullable|string',
            'contacts.*.pincode' => 'nullable|string',
            'payment_method_ids' => 'sometimes|array',
            'payment_method_ids.*' => 'integer',
            'payment_providers' => 'sometimes|array',
            'payment_providers.*.payment_id' => 'required_with:payment_providers|integer',
            'payment_providers.*.merchant_id' => 'nullable|string|max:255',
            'payment_providers.*.secret_key' => 'nullable|string|max:500',
            'business_hours' => 'sometimes|array',
            'business_hours.*.day_of_week' => ['required_with:business_hours', Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])],
            'business_hours.*.is_open' => 'sometimes|boolean',
            'business_hours.*.opening_time' => 'nullable|date_format:H:i',
            'business_hours.*.closing_time' => 'nullable|date_format:H:i',
            'business_hours.*.slot_index' => 'nullable|integer|min:1|max:10',
            'settings' => 'sometimes|array',
            'settings.enforce_2fa' => 'sometimes|boolean',
            'settings.geo_location_tracking' => 'sometimes|boolean',
            'settings.geo_fencing_enabled' => 'sometimes|boolean',
            'settings.geo_fencing_radius' => 'nullable|required_if:settings.geo_fencing_enabled,1|integer|min:1|max:100000',
            'settings.appointment_time_slice_enabled' => 'sometimes|boolean',
            'settings.appointment_time_slice_minutes' => 'nullable|required_if:settings.appointment_time_slice_enabled,1|integer|min:1|max:1440',
            'settings.auto_approve_appointments' => 'sometimes|boolean',
            'settings.marketing_message' => 'nullable|string|max:5000',
            'settings.public_company_page' => 'sometimes|boolean',
            'taxes' => 'sometimes|array',
            'taxes.*.tax_name' => 'required_with:taxes|string',
            'taxes.*.components' => 'sometimes|array',
            'country_code' => 'nullable|string|size:2',
            'selected_template_ids' => 'sometimes|array',
            'selected_template_ids.*' => 'integer',
            'apply_all_country_tax_templates' => 'sometimes|boolean',
            'feature_ids' => 'sometimes|array',
            'feature_ids.*' => 'integer',
        ]);
        $v->after(function ($validator) use ($request) {
            foreach ((array) $request->input('contacts', []) as $idx => $contact) {
                $country = trim((string) ($contact['country'] ?? ''));
                $state = trim((string) ($contact['state'] ?? ''));
                $city = trim((string) ($contact['city'] ?? ''));
                if ($state !== '' && $country === '') {
                    $validator->errors()->add("contacts.$idx.country", 'Country is required when state is provided.');
                }
                if ($city !== '' && ($country === '' || $state === '')) {
                    $validator->errors()->add("contacts.$idx.city", 'City requires both country and state.');
                }
            }
            $selected = array_map('intval', (array) $request->input('payment_method_ids', []));
            $providers = collect((array) $request->input('payment_providers', []))->keyBy(function ($row) {
                return (int) ($row['payment_id'] ?? 0);
            });
            if (!empty($selected)) {
                $customIds = PaymentMethods::query()
                    ->whereIn('payment_id', $selected)
                    ->where(function ($q) {
                        $q->where('payment_name', 'like', '%stripe%')
                            ->orWhere('payment_name', 'like', '%razorpay%')
                            ->orWhere('payment_name', 'like', '%custom%');
                    })
                    ->pluck('payment_id')
                    ->map(fn ($id) => (int) $id)
                    ->all();
                foreach ($customIds as $pid) {
                    $provider = $providers->get($pid, []);
                    if (empty($provider['merchant_id'])) {
                        $validator->errors()->add("payment_providers.$pid.merchant_id", 'Merchant ID is required for selected custom providers.');
                    }
                    if (empty($provider['secret_key'])) {
                        $validator->errors()->add("payment_providers.$pid.secret_key", 'Secret key is required for selected custom providers.');
                    }
                }
            }
            if ($request->boolean('apply_all_country_tax_templates')) {
                $cc = strtoupper(trim((string) $request->input('country_code', '')));
                if ($cc === '' || strlen($cc) !== 2) {
                    $validator->errors()->add('country_code', 'A valid ISO country code is required when applying all templates for that country.');
                }
            }
        });
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $v->errors()], 422);
        }

        /** @var AdminUser|null $admin */
        $admin = $request->attributes->get('admin_user');
        if (!$admin) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        try {
            $companyPayload = $request->input('company', []);
            // Enforce: auto-confirm only when customer app is enabled
            if (empty($companyPayload['customer_app'])) {
                $companyPayload['appointment_auto_confirm'] = false;
            }
            $request->merge(['company' => $companyPayload]);

            $company = $this->companyService->createCompany(
                $request->only([
                    'company',
                    'branches',
                    'contacts',
                    'payment_method_ids',
                    'payment_providers',
                    'business_hours',
                    'settings',
                    'taxes',
                    'country_code',
                    'selected_template_ids',
                    'apply_all_country_tax_templates',
                    'feature_ids',
                ]),
                $admin->email,
                $request->input('pin')
            );
            $company->setAttribute('company_logo_urls', CompanyLogoStorageService::publicUrls($company->company_logo));

            return response()->json(['success' => true, 'message' => 'Company created.', 'data' => $company], 201);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * PUT /admin/company/{id} — update company (partial).
     */
    public function update(Request $request, int $id): JsonResponse
    {
        if ($request->has('company_website')) {
            $request->merge([
                'company_website' => $this->normalizeCompanyWebsiteInput((string) $request->input('company_website')),
            ]);
        }
        if ($request->has('email')) {
            $em = trim((string) $request->input('email'));
            $request->merge(['email' => $em !== '' ? $em : null]);
        }

        $v = Validator::make($request->all(), [
            'company_name' => 'sometimes|string|max:255',
            'legal_name' => 'sometimes|string|max:255',
            'tag_line' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'phone_number' => ['nullable', 'string', 'max:32', 'regex:/^\+?[0-9\-\s\(\)]{7,32}$/'],
            'email' => ['sometimes', 'nullable', 'email', 'max:255', Rule::unique('company_detail', 'email')->ignore($id, 'company_id')],
            'company_status' => 'sometimes',
            'company_business_id' => 'sometimes|integer',
            'company_website' => 'nullable|url|max:255',
            'company_dawn' => 'sometimes|string|max:20',
            'company_dusk' => 'sometimes|string|max:20',
            'company_logo' => 'nullable|string|max:64',
            'customer_app' => 'sometimes|boolean',
            'appointment_auto_confirm' => 'sometimes|boolean',
            'bank_name' => 'nullable|string|max:255',
            'bank_code' => 'nullable|string|max:64',
            'account_name' => 'nullable|string|max:255',
            'account_number' => 'nullable|string|max:64',
            'business_hours' => 'sometimes|array',
            'business_hours.*.day_of_week' => ['required_with:business_hours', Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])],
            'business_hours.*.is_open' => 'sometimes|boolean',
            'business_hours.*.opening_time' => 'nullable|date_format:H:i',
            'business_hours.*.closing_time' => 'nullable|date_format:H:i',
            'business_hours.*.slot_index' => 'nullable|integer|min:1|max:10',
            'settings' => 'sometimes|array',
            'settings.enforce_2fa' => 'sometimes|boolean',
            'settings.geo_location_tracking' => 'sometimes|boolean',
            'settings.geo_fencing_enabled' => 'sometimes|boolean',
            'settings.geo_fencing_radius' => 'nullable|required_if:settings.geo_fencing_enabled,1|integer|min:1|max:100000',
            'settings.appointment_time_slice_enabled' => 'sometimes|boolean',
            'settings.appointment_time_slice_minutes' => 'nullable|required_if:settings.appointment_time_slice_enabled,1|integer|min:1|max:1440',
            'settings.auto_approve_appointments' => 'sometimes|boolean',
            'settings.marketing_message' => 'nullable|string|max:5000',
            'settings.public_company_page' => 'sometimes|boolean',
            'country_code' => 'nullable|string|size:2',
            'selected_template_ids' => 'sometimes|array',
            'selected_template_ids.*' => 'integer',
            'apply_all_country_tax_templates' => 'sometimes|boolean',
        ]);
        $v->after(function ($validator) use ($request) {
            if ($request->boolean('apply_all_country_tax_templates')) {
                $cc = strtoupper(trim((string) $request->input('country_code', '')));
                if ($cc === '' || strlen($cc) !== 2) {
                    $validator->errors()->add('country_code', 'A valid ISO country code is required when applying all templates for that country.');
                }
            }
        });
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }
        try {
            return DB::transaction(function () use ($request, $id) {
                $patch = $request->only([
                    'company_name',
                    'legal_name',
                    'tag_line',
                    'description',
                    'phone_number',
                    'email',
                    'company_status',
                    'company_business_id',
                    'company_website',
                    'company_dawn',
                    'company_dusk',
                    'company_logo',
                    'customer_app',
                    'appointment_auto_confirm',
                    'bank_name',
                    'bank_code',
                    'account_name',
                    'account_number',
                    'business_hours',
                    'settings',
                ]);
                if (array_key_exists('customer_app', $patch) && empty($patch['customer_app'])) {
                    $patch['appointment_auto_confirm'] = false;
                }
                $company = $this->companyService->update($id, $patch);
                if ($request->hasAny(['country_code', 'selected_template_ids', 'apply_all_country_tax_templates'])) {
                    $this->companyService->applyTaxTemplatesFromCountrySelection(
                        $id,
                        $request->only(['country_code', 'selected_template_ids', 'apply_all_country_tax_templates'])
                    );
                }
                $company->setAttribute('company_logo_urls', CompanyLogoStorageService::publicUrls($company->company_logo));

                return response()->json(['success' => true, 'data' => $company], 200);
            });
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Company not found.'], 404);
        }
    }

    /**
     * POST /admin/company/{id}/logo — multipart logo upload; stores files on disk, saves UUID key only.
     */
    public function uploadLogo(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'logo' => 'required|file|mimes:jpeg,jpg,png,svg|max:2048',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation failed.', 'errors' => $v->errors()], 422);
        }

        try {
            $company = \App\CompanyDetail::findOrFail($id);
            $oldKey = $company->company_logo;
            $key = $this->logoStorage->store($request->file('logo'));
            if ($oldKey && $oldKey !== $key) {
                $this->logoStorage->deleteByKey($oldKey);
            }
            $this->companyService->update($id, ['company_logo' => $key]);

            return response()->json([
                'success' => true,
                'data' => [
                    'company_logo' => $key,
                    'company_logo_urls' => CompanyLogoStorageService::publicUrls($key),
                ],
            ], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * DELETE /admin/company/{id}/logo — remove logo files and clear column.
     */
    public function deleteLogo(int $id): JsonResponse
    {
        try {
            $company = \App\CompanyDetail::findOrFail($id);
            $this->logoStorage->deleteByKey($company->company_logo);
            $this->companyService->update($id, ['company_logo' => null]);

            return response()->json(['success' => true, 'message' => 'Logo removed.'], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * PUT /admin/company/{id}/branches — sync branches + contacts.
     */
    public function updateBranches(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'branches' => 'required|array',
            'branches.*.branch_name' => 'required|string|max:255',
            'branches.*.latitude' => 'nullable|numeric|between:-90,90',
            'branches.*.longitude' => 'nullable|numeric|between:-180,180',
            'branches.*.branch_status' => 'sometimes|integer',
            'branches.*.branch_type' => 'nullable|string',
            'branches.*.head_branch' => 'sometimes|boolean',
            'branches.*.branch_id' => 'sometimes|integer',
            'contacts' => 'required|array',
            'contacts.*.phone' => 'nullable|string',
            'contacts.*.email' => 'nullable|email',
            'contacts.*.address1' => 'nullable|string',
            'contacts.*.city' => 'nullable|string',
            'contacts.*.state' => 'nullable|string',
            'contacts.*.country' => 'nullable|string',
            'contacts.*.pincode' => 'nullable|string',
        ]);
        $v->after(function ($validator) use ($request) {
            foreach ((array) $request->input('contacts', []) as $idx => $contact) {
                $country = trim((string) ($contact['country'] ?? ''));
                $state = trim((string) ($contact['state'] ?? ''));
                $city = trim((string) ($contact['city'] ?? ''));
                if ($state !== '' && $country === '') {
                    $validator->errors()->add("contacts.$idx.country", 'Country is required when state is provided.');
                }
                if ($city !== '' && ($country === '' || $state === '')) {
                    $validator->errors()->add("contacts.$idx.city", 'City requires both country and state.');
                }
            }
        });
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }
        try {
            $this->companyService->syncBranchesAndContacts(
                $id,
                $request->input('branches', []),
                $request->input('contacts', [])
            );
            $company = $this->companyService->getOne($id);

            return response()->json(['success' => true, 'data' => $company], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * PUT /admin/company/{id}/payment-methods — replace selected payment methods.
     */
    public function updatePaymentMethods(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'payment_method_ids' => 'required|array',
            'payment_method_ids.*' => 'integer',
            'payment_providers' => 'sometimes|array',
            'payment_providers.*.payment_id' => 'required_with:payment_providers|integer',
            'payment_providers.*.merchant_id' => 'nullable|string|max:255',
            'payment_providers.*.secret_key' => 'nullable|string|max:500',
        ]);
        $v->after(function ($validator) use ($request) {
            $selected = array_map('intval', (array) $request->input('payment_method_ids', []));
            $providers = collect((array) $request->input('payment_providers', []))->keyBy(function ($row) {
                return (int) ($row['payment_id'] ?? 0);
            });
            if (empty($selected)) {
                return;
            }
            $customIds = PaymentMethods::query()
                ->whereIn('payment_id', $selected)
                ->where(function ($q) {
                    $q->where('payment_name', 'like', '%stripe%')
                        ->orWhere('payment_name', 'like', '%razorpay%')
                        ->orWhere('payment_name', 'like', '%custom%');
                })
                ->pluck('payment_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            foreach ($customIds as $pid) {
                $provider = $providers->get($pid, []);
                if (empty($provider['merchant_id'])) {
                    $validator->errors()->add("payment_providers.$pid.merchant_id", 'Merchant ID is required for selected custom providers.');
                }
                if (empty($provider['secret_key'])) {
                    $validator->errors()->add("payment_providers.$pid.secret_key", 'Secret key is required for selected custom providers.');
                }
            }
        });
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }
        try {
            $this->companyService->syncPaymentMethods(
                $id,
                $request->input('payment_method_ids', []),
                $request->input('payment_providers', [])
            );
            $company = $this->companyService->getOne($id);

            return response()->json(['success' => true, 'data' => $company], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * PUT /admin/company/{id}/business-hours — replace weekly business hours.
     */
    public function updateBusinessHours(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'business_hours' => 'required|array|min:1',
            'business_hours.*.day_of_week' => ['required', Rule::in(['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'])],
            'business_hours.*.is_open' => 'sometimes|boolean',
            'business_hours.*.opening_time' => 'nullable|date_format:H:i',
            'business_hours.*.closing_time' => 'nullable|date_format:H:i',
            'business_hours.*.slot_index' => 'nullable|integer|min:1|max:10',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }
        try {
            $this->companyService->syncBusinessHours($id, $request->input('business_hours', []));
            $company = $this->companyService->getOne($id);

            return response()->json(['success' => true, 'data' => $company], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * PUT /admin/company/{id}/settings — upsert company settings module.
     */
    public function updateSettings(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.enforce_2fa' => 'sometimes|boolean',
            'settings.geo_location_tracking' => 'sometimes|boolean',
            'settings.geo_fencing_enabled' => 'sometimes|boolean',
            'settings.geo_fencing_radius' => 'nullable|required_if:settings.geo_fencing_enabled,1|integer|min:1|max:100000',
            'settings.appointment_time_slice_enabled' => 'sometimes|boolean',
            'settings.appointment_time_slice_minutes' => 'nullable|required_if:settings.appointment_time_slice_enabled,1|integer|min:1|max:1440',
            'settings.auto_approve_appointments' => 'sometimes|boolean',
            'settings.marketing_message' => 'nullable|string|max:5000',
            'settings.public_company_page' => 'sometimes|boolean',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }
        try {
            $this->companyService->upsertSettings($id, $request->input('settings', []));
            $company = $this->companyService->getOne($id);

            return response()->json(['success' => true, 'data' => $company], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * PUT /admin/company/{id}/taxes — replace tax configuration for the company.
     */
    public function updateTaxes(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'taxes' => 'required|array',
            'taxes.*.tax_name' => 'required_with:taxes|string',
            'taxes.*.components' => 'sometimes|array',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }
        try {
            $this->companyService->syncTaxes($id, $request->input('taxes', []));
            $company = $this->companyService->getOne($id);

            return response()->json(['success' => true, 'data' => $company], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * PUT /admin/company/{id}/features — replace selected features.
     */
    public function updateFeatures(Request $request, int $id): JsonResponse
    {
        $v = Validator::make($request->all(), [
            'feature_ids' => 'required|array',
            'feature_ids.*' => 'integer',
        ]);
        if ($v->fails()) {
            return response()->json(['success' => false, 'errors' => $v->errors()], 422);
        }
        try {
            $this->companyService->syncFeatures($id, $request->input('feature_ids', []));
            $company = $this->companyService->getOne($id);

            return response()->json(['success' => true, 'data' => $company], 200);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * DELETE /admin/company/{id} — delete company (hard delete; cascade via FK).
     */
    public function destroy(int $id): JsonResponse
    {
        try {
            $this->companyService->delete($id);
            return response()->json(['success' => true, 'message' => 'Company deleted.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['success' => false, 'message' => 'Company not found.'], 404);
        }
    }

    /**
     * GET /admin/company/dropdowns/payment-methods — list for step 4.
     */
    public function paymentMethods(): JsonResponse
    {
        $list = PaymentMethods::where('active_status', 1)->orderBy('payment_name')->get();
        return response()->json(['success' => true, 'data' => $list], 200);
    }

    /**
     * GET /admin/company/dropdowns/features — list for step 6.
     */
    public function features(): JsonResponse
    {
        $list = AppFeatures::whereIn('feature_status', ['A', '1', 1, 'Active'])->orderBy('feature_name')->get();
        return response()->json(['success' => true, 'data' => $list], 200);
    }

    /**
     * GET /admin/company/dropdowns/tax-templates — country tax blueprints (?country_code=ISO2) or legacy global samples.
     */
    public function taxTemplates(Request $request): JsonResponse
    {
        if (Schema::hasTable('tax_master_template')) {
            $q = TaxMasterTemplate::query()
                ->with(['components.detailRows'])
                ->where('is_active', 1)
                ->orderBy('tax_name');
            $cc = $request->query('country_code');
            if (is_string($cc) && strlen(trim($cc)) === 2) {
                $q->where('country_code', strtoupper(trim($cc)));
            }
            if ($request->has('region_code')) {
                $rc = $request->query('region_code');
                if (is_string($rc) && trim($rc) !== '') {
                    $q->where('region_code', strtoupper(trim($rc)));
                }
            }
            $list = $q->limit(200)->get();

            return response()->json(['success' => true, 'data' => $list], 200);
        }

        $list = TaxMaster::with('components.details')->whereNull('company_id')->limit(50)->get();

        return response()->json(['success' => true, 'data' => $list], 200);
    }

    /**
     * GET /admin/company/dropdowns/countries — for address forms (country first).
     */
    public function countries(): JsonResponse
    {
        $list = Country::query()
            ->orderBy('country_name')
            ->get(['country_id', 'country_name', 'country_code']);

        return response()->json(['success' => true, 'data' => $list], 200);
    }

    /**
     * GET /public/company-logo/{key}/{variant}
     * Streams logo bytes directly from storage/public/company-logos.
     */
    public function publicLogo(string $key, string $variant)
    {
        if (!preg_match('/^[a-zA-Z0-9\-]{20,80}$/', $key)) {
            abort(404);
        }

        $variant = strtolower(trim($variant));
        $map = [
            'sm' => '64',
            'md' => '128',
            'lg' => '256',
            'original' => 'original',
        ];
        if (!array_key_exists($variant, $map)) {
            abort(404);
        }

        $prefix = 'company-logos/' . $key;
        $disk = Storage::disk('public');
        if (!$disk->exists($prefix)) {
            abort(404);
        }

        $isSvg = $disk->exists($prefix . '/.format') && trim((string) $disk->get($prefix . '/.format')) === 'svg';
        $filename = $map[$variant] . ($isSvg ? '.svg' : '.jpg');
        $path = $prefix . '/' . $filename;
        if (!$disk->exists($path)) {
            abort(404);
        }

        $bytes = $disk->get($path);
        $mime = $isSvg ? 'image/svg+xml' : 'image/jpeg';

        return response($bytes, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=86400',
        ]);
    }

    /** Trim; empty → null; host-only values get https:// so `url` validation matches admin UI behaviour. */
    private function normalizeCompanyWebsiteInput(string $raw): ?string
    {
        $s = trim($raw);
        if ($s === '') {
            return null;
        }
        if (!preg_match('#^https?://#i', $s)) {
            $s = 'https://'.$s;
        }

        return $s;
    }
}
