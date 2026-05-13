<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;

use API\ChannelController;
use API\AppEventsController;
use API\CommentsController;
use App\Http\Controllers\API\OrderController;
use App\Http\Controllers\API\InvoiceController;
use App\Http\Controllers\API\ProformaController;
use App\Http\Controllers\API\AttendanceController;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\UserController;
use App\Http\Controllers\API\RecipeController;
use App\Http\Controllers\API\UserAdditionalDetailController;
use App\Http\Controllers\API\UserAddressController;
use App\Http\Controllers\AdminNotificationsController;

// use API\NotificationController;



/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });
Route::apiResource('/channel', ChannelController::class);
Route::apiResource('/event', AppEventsController::class);


Route::group(['prefix' => 'v1', 'namespace' => 'API'], function ($app) {

    Route::get('/ping', function () {
        return response()->json(['message' => 'pong'], 200);
    });
    Route::post('/verify-phone', [AuthController::class, 'verifyPhone'])->name('verify-phone');
    Route::post('/verify-pin', [AuthController::class, 'verifyPin'])->name('verify-pin');
    Route::middleware('auth:sanctum')->get('/user', [UserController::class, 'getUser']);
    Route::middleware('auth:sanctum')->post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/notify', 'NotificationController@index');
    Route::post('/notify', 'NotificationController@sendNormalNotification');
    Route::post('/notification/list', 'NotificationController@list');
    Route::post('/notification/read', 'NotificationController@read');
    Route::post('/notification/count', 'NotificationController@count');
    Route::post('/verify/phoneno', 'NotificationController@verifyPhoneNo');
    Route::get('/app/features', 'FeaturesController@index');
    Route::post('/app/features', 'FeaturesController@update');
    Route::get('/products', [\App\Http\Controllers\API\ProductItemsController::class, 'index']);
    Route::post('/notification/promotional', 'NotificationController@bulk');
    Route::post('/notification/company/promotional', 'NotificationController@bulkList');

    // Jobs Routes 
    Route::get('/jobs', 'JobsController@index');
    Route::get('/job/{code}', 'JobsController@show');
    Route::post('/job', 'JobsController@store');
    Route::put('/job/{code}', 'JobsController@update');
    Route::delete('/job/{code}', 'JobsController@destroy');


    // Invoice Mailing 
    Route::post('/invoice/customer/email', 'InvoiceController@getCustomerEmails');
    Route::post('/invoice/mail', 'InvoiceController@mail');
    Route::get('/invoice/{invoiceNo}/print', 'InvoiceController@print');
    Route::get('/invoice/{invoiceNo}/share', 'InvoiceController@share');
    Route::get('/test-email', 'NotificationController@enqueue');
    Route::post('/order/generate', [OrderController::class, 'generateOrder']);
    Route::post('/latest/orders', [InvoiceController::class, 'latestCompletedInvoice']);

    Route::get('/proforma/{proformaNo}/print', 'InvoiceController@proforma');


    // Add User to Company 
    Route::post('/company/add/user', 'UserCompaniesController@mapping');
    Route::post('/send/otp', 'NotificationController@sendOtp');

    // Comments 
    Route::get('/note/{notes}', 'CommentsController@index');
    // Route::apiResource('/comment', CommentsController::class);
    Route::apiResource('comment', 'CommentsController');
    // Route::post('/comment','CommentsController@store');
    // Route::put('/comment/{comment}','CommentsController@update');
    // Route::delete('/comment/{comment}','CommentsController@delete');

    // Locale
    Route::get('/locale', 'LocaleController@index');
    Route::get('locale/company/{companyId}', 'LocaleController@company');
    Route::get('label/{code}', 'LabelsController@index');

    /* ORDER MANAGEMENT */
    Route::prefix('order')->group(function () {
        Route::post('/calculate-summary', [OrderController::class, 'calculateSummary']);
        Route::post('/confirm', [OrderController::class, 'confirmOrder']);
        Route::get('{orderNumber}', [OrderController::class, 'show'])->name('order.show');
        Route::get('{orderNumber}/detailed-pdf', [OrderController::class, 'export']);
        Route::get('{orderNumber}/preview', [OrderController::class, 'preview']);
    });

    /*  PROFORMA MANAGEMENT  */
    Route::prefix('proforma')->group(function () {
        Route::post('/calculate-summary', [ProformaController::class, 'calculateSummary']);
        Route::post('/generate', [ProformaController::class, 'generate'])->name('proforma.create');
        Route::get('/{proformaNo}', [ProformaController::class, 'show'])->name('proforma.show');
        Route::get('/{proformaNo}/pdf', [ProformaController::class, 'export'])->name('proforma.pdf');
        Route::post('/list', [ProformaController::class, 'list'])->name('proforma.index');

        // Route::get('/details/{id}', [ProformaController::class, 'details']);
        // Route::post('/convert-to-order/{id}', [ProformaController::class, 'convertToOrder']);
    });
    // Route::get('/user-details', [UserAdditionalDetailController::class, 'index']); // List details for a specific user
    Route::post('/user-details', [UserAdditionalDetailController::class, 'store']); // Create or update details
    Route::get('/user-details/{id}', [UserAdditionalDetailController::class, 'show']); // Show specific user details
    Route::put('/user-details/{id}', [UserAdditionalDetailController::class, 'update']); // Update user details
    Route::delete('/user-details/{id}', [UserAdditionalDetailController::class, 'destroy']); // Delete user details



    Route::prefix('attendance')->group(function () {
        Route::post('/punch-in', [AttendanceController::class, 'punchIn']);
        Route::post('/punch-out', [AttendanceController::class, 'punchOut']);
        Route::post('/check', [AttendanceController::class, 'checkAttendance']);
        Route::post('/history', [AttendanceController::class, 'getMonthlyAttendance']);

        Route::get('/analytics', [AttendanceController::class, 'analytics']);
        Route::get('/export', [AttendanceController::class, 'exportExcel']);

        // Route::get('/export/{id}', [ProformaExportController::class, 'export']);
        // Route::get('/list', [ProformaController::class, 'list']);
        // Route::get('/details/{id}', [ProformaController::class, 'details']);
        // Route::post('/convert-to-order/{id}', [ProformaController::class, 'convertToOrder']);
    });

    Route::get('recipes', [RecipeController::class, 'index']);
    Route::post('recipes', [RecipeController::class, 'store']);
    Route::get('recipes/{id}', [RecipeController::class, 'show']);
    Route::delete('recipes', [RecipeController::class, 'destory']);
    // Route::apiResource('recipes', RecipeController::class);
    Route::prefix('users')->group(function () {

        // ── Address CRUD ───────────────────────────────────────────────────────
        Route::get('/{user_id}/addresses',                         [UserAddressController::class, 'getAddresses']);   // GET    /api/users/{user_id}/addresses
        Route::post('/{user_id}/addresses',                         [UserAddressController::class, 'addAddress']);     // POST   /api/users/{user_id}/addresses
        Route::put('/{user_id}/addresses/{contact_id}',            [UserAddressController::class, 'updateAddress']);  // PUT    /api/users/{user_id}/addresses/{contact_id}
        Route::delete('/{user_id}/addresses/{contact_id}',            [UserAddressController::class, 'deleteAddress']);  // DELETE /api/users/{user_id}/addresses/{contact_id}
        Route::patch('/{user_id}/addresses/{contact_id}/set-default', [UserAddressController::class, 'setDefaultAddress']); // PATCH /api/users/{user_id}/addresses/{contact_id}/set-default

        // ── Home Screen  (optimised single-query endpoint) ─────────────────────
        Route::get('/{user_id}/primary-address', [UserAddressController::class, 'getPrimaryAddress']); // GET /api/users/{user_id}/primary-address
    });
    /* USER MANAGEMENT  */
    Route::prefix('users')->group(function () {
        Route::post('/register', [UserController::class, 'register']);
        Route::post('/verify-otp', [AuthController::class, 'authOtp'])->name('auth.verify-otp');
        Route::post('/resend-otp', [AuthController::class, 'resendOtp'])->name('auth.resend-otp');
    });
    Route::get('/otp', function () {
        return view('mails.credentials', [
            'name' => 'Mohammed Khwaja Nizamuddin',
            'phone' => '6476692343',
            'pin'  => '5456',
            'dashboardUrl' => 'https://ca-business.bizwy.in/v1/login',
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| Admin Auth Module (isolated: /admin/auth/* — no changes to existing APIs)
|--------------------------------------------------------------------------
*/
Route::prefix('admin/auth')->namespace('App\Modules\AdminAuth\Http\Controllers')->group(function () {
    // NOTE (development): rate-limit middleware temporarily disabled.
    Route::post('/login', [\App\Modules\AdminAuth\Http\Controllers\AdminAuthController::class, 'login']);
    Route::post('/verify-pin', [\App\Modules\AdminAuth\Http\Controllers\AdminAuthController::class, 'verifyPin']);
    Route::post('/verify-otp', [\App\Modules\AdminAuth\Http\Controllers\AdminAuthController::class, 'verifyOtp']);
    Route::post('/refresh-token', [\App\Modules\AdminAuth\Http\Controllers\AdminAuthController::class, 'refreshToken']);
    Route::post('/logout', [\App\Modules\AdminAuth\Http\Controllers\AdminAuthController::class, 'logout']);
    Route::post('/forgot-pin', [\App\Modules\AdminAuth\Http\Controllers\AdminAuthController::class, 'forgotPin']);
    Route::post('/reset-pin', [\App\Modules\AdminAuth\Http\Controllers\AdminAuthController::class, 'resetPin']);

    Route::middleware('adminAuth')->group(function () {
        Route::get('/me', [\App\Modules\AdminAuth\Http\Controllers\AdminAuthController::class, 'me']);
        Route::post('/change-pin', [\App\Modules\AdminAuth\Http\Controllers\AdminAuthController::class, 'changePin']);
    });
});

/*
|--------------------------------------------------------------------------
| Admin: super users — global list (all companies + company names)
| GET /api/admin/super-users
|--------------------------------------------------------------------------
*/
Route::middleware('adminAuth')->group(function () {
    Route::get('admin/super-users', [\App\Modules\AdminCompany\Http\Controllers\SuperUserController::class, 'globalIndex']);
});

/*
|--------------------------------------------------------------------------
| Admin: platform admins — super-admin only (manage portal administrator accounts)
| /api/admin/platform-admins/*
|--------------------------------------------------------------------------
*/
Route::prefix('admin/platform-admins')->middleware(['adminAuth', 'superAdmin'])->group(function () {
    Route::get('/', [\App\Modules\AdminPlatformAdmin\Http\Controllers\PlatformAdminController::class, 'index']);
    Route::post('/', [\App\Modules\AdminPlatformAdmin\Http\Controllers\PlatformAdminController::class, 'store']);
    Route::get('/{id}', [\App\Modules\AdminPlatformAdmin\Http\Controllers\PlatformAdminController::class, 'show']);
    Route::delete('/{id}', [\App\Modules\AdminPlatformAdmin\Http\Controllers\PlatformAdminController::class, 'destroy']);
    Route::put('/{id}', [\App\Modules\AdminPlatformAdmin\Http\Controllers\PlatformAdminController::class, 'update']);
    Route::patch('/{id}/status', [\App\Modules\AdminPlatformAdmin\Http\Controllers\PlatformAdminController::class, 'patchStatus']);
    Route::post('/{id}/reset-pin', [\App\Modules\AdminPlatformAdmin\Http\Controllers\PlatformAdminController::class, 'resetPin']);
});

/*
|--------------------------------------------------------------------------
| Super users (V1 tables — CRUD scoped by company — admin JWT)
| GET /api/companies/{id}/super-users ...
|--------------------------------------------------------------------------
*/
Route::prefix('companies')->middleware('adminAuth')->group(function () {
    Route::get('{company_id}/super-users/mobile-check', [\App\Modules\AdminCompany\Http\Controllers\SuperUserController::class, 'mobileCheck']);
    Route::get('{company_id}/super-users/modules', [\App\Modules\AdminCompany\Http\Controllers\SuperUserController::class, 'modulesForCompany']);
    Route::post('{company_id}/super-users/{user_id}/resend-welcome', [\App\Modules\AdminCompany\Http\Controllers\SuperUserController::class, 'resendWelcome']);
    Route::post('{company_id}/super-users/{user_id}/reset-pin', [\App\Modules\AdminCompany\Http\Controllers\SuperUserController::class, 'resetPin']);
    Route::post('{company_id}/super-users/{user_id}/resend-pin', [\App\Modules\AdminCompany\Http\Controllers\SuperUserController::class, 'resendPin']);
    Route::patch('{company_id}/super-users/{user_id}/reactivate', [\App\Modules\AdminCompany\Http\Controllers\SuperUserController::class, 'reactivate']);
    Route::get('{company_id}/super-users', [\App\Modules\AdminCompany\Http\Controllers\SuperUserController::class, 'index']);
    Route::get('{company_id}/super-users/{user_id}', [\App\Modules\AdminCompany\Http\Controllers\SuperUserController::class, 'show']);
    Route::put('{company_id}/super-users/{user_id}', [\App\Modules\AdminCompany\Http\Controllers\SuperUserController::class, 'update']);
    Route::delete('{company_id}/super-users/{user_id}', [\App\Modules\AdminCompany\Http\Controllers\SuperUserController::class, 'destroy']);
    Route::post('{company_id}/super-user', [\App\Modules\AdminCompany\Http\Controllers\SuperUserController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Admin Company Module (isolated: /admin/company/* — no changes to existing APIs)
|--------------------------------------------------------------------------
*/
Route::prefix('admin/company')->middleware('adminAuth')->namespace('App\Modules\AdminCompany\Http\Controllers')->group(function () {
    Route::get('/dropdowns/payment-methods', [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'paymentMethods']);
    Route::get('/dropdowns/features', [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'features']);
    Route::get('/dropdowns/tax-templates', [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'taxTemplates']);
    Route::get('/dropdowns/countries', [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'countries']);
    Route::get('/', [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'index']);
    Route::post('/', [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'store']);
    Route::post('/{id}/logo', [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'uploadLogo']);
    Route::delete('/{id}/logo', [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'deleteLogo']);
    Route::put('/{id}/branches', [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'updateBranches']);
    Route::put('/{id}/payment-methods', [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'updatePaymentMethods']);
    Route::put('/{id}/business-hours', [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'updateBusinessHours']);
    Route::put('/{id}/settings', [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'updateSettings']);
    Route::put('/{id}/taxes', [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'updateTaxes']);
    Route::put('/{id}/features', [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'updateFeatures']);
    Route::get('/{id}', [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'show']);
    Route::put('/{id}', [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'update']);
    Route::delete('/{id}', [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Public company logo streaming endpoint (no storage:link dependency)
|--------------------------------------------------------------------------
*/
Route::get(
    'public/company-logo/{key}/{variant}',
    [\App\Modules\AdminCompany\Http\Controllers\AdminCompanyController::class, 'publicLogo']
)->where('variant', 'sm|md|lg|original');

/*
|--------------------------------------------------------------------------
| Admin dashboard — platform metrics (read-only aggregates)
|--------------------------------------------------------------------------
*/
Route::prefix('admin/dashboard')->middleware('adminAuth')->group(function () {
    Route::get('/summary', [\App\Modules\AdminDashboard\Http\Controllers\AdminPlatformDashboardController::class, 'summary']);
    Route::get('/companies', [\App\Modules\AdminDashboard\Http\Controllers\AdminPlatformDashboardController::class, 'companies']);
    Route::get('/growth', [\App\Modules\AdminDashboard\Http\Controllers\AdminPlatformDashboardController::class, 'growth']);
    Route::get('/uploads', [\App\Modules\AdminDashboard\Http\Controllers\AdminPlatformDashboardController::class, 'uploads']);
    Route::get('/alerts', [\App\Modules\AdminDashboard\Http\Controllers\AdminPlatformDashboardController::class, 'alerts']);
});

Route::get('admin/notifications', [AdminNotificationsController::class, 'index'])->middleware('adminAuth');

/*
|--------------------------------------------------------------------------
| Admin platform mail settings (persisted overrides for config/mail — not .env)
|--------------------------------------------------------------------------
*/
Route::prefix('admin/platform/mail-settings')->middleware('adminAuth')->group(function () {
    Route::get('/', [\App\Modules\AdminPlatformMail\Http\Controllers\PlatformMailSettingsController::class, 'show']);
    Route::put('/', [\App\Modules\AdminPlatformMail\Http\Controllers\PlatformMailSettingsController::class, 'update']);
    Route::post('/test', [\App\Modules\AdminPlatformMail\Http\Controllers\PlatformMailSettingsController::class, 'sendTest']);
});

/*
|--------------------------------------------------------------------------
| Admin catalogues — merchant_catalogue listing & creation
|--------------------------------------------------------------------------
*/
Route::prefix('admin/catalogues')->middleware('adminAuth')->group(function () {
    Route::get('/', [\App\Modules\AdminProducts\Http\Controllers\AdminCatalogueController::class, 'index']);
    Route::post('/', [\App\Modules\AdminProducts\Http\Controllers\AdminCatalogueController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Admin products — bulk upload template (XLSX, read-only tax/branch/catalogue meta)
|--------------------------------------------------------------------------
*/
Route::prefix('admin/products')->middleware('adminAuth')->group(function () {
    Route::get('/template-meta', [\App\Modules\AdminProducts\Http\Controllers\AdminProductTemplateController::class, 'templateMeta']);
    Route::get('/template', [\App\Modules\AdminProducts\Http\Controllers\AdminProductTemplateController::class, 'template']);
    Route::post('/bulk-upload', [\App\Modules\AdminProducts\Http\Controllers\AdminProductBulkUploadController::class, 'store']);
    Route::get('/bulk-upload/result', [\App\Modules\AdminProducts\Http\Controllers\AdminProductBulkUploadController::class, 'result']);
});

/*
|--------------------------------------------------------------------------
| Admin Tax Templates (country blueprints → clone into tenant tax tables)
|--------------------------------------------------------------------------
*/
Route::prefix('admin/tax-template')->middleware('adminAuth')->group(function () {
    Route::get('/', [\App\Modules\AdminTaxTemplate\Http\Controllers\AdminTaxTemplateController::class, 'index']);
    Route::post('/', [\App\Modules\AdminTaxTemplate\Http\Controllers\AdminTaxTemplateController::class, 'store']);
    Route::get('/{id}', [\App\Modules\AdminTaxTemplate\Http\Controllers\AdminTaxTemplateController::class, 'show'])->whereNumber('id');
    Route::put('/{id}', [\App\Modules\AdminTaxTemplate\Http\Controllers\AdminTaxTemplateController::class, 'update'])->whereNumber('id');
    Route::post('/{id}/deactivate', [\App\Modules\AdminTaxTemplate\Http\Controllers\AdminTaxTemplateController::class, 'deactivate'])->whereNumber('id');
    Route::delete('/{id}', [\App\Modules\AdminTaxTemplate\Http\Controllers\AdminTaxTemplateController::class, 'destroy'])->whereNumber('id');
});

/*
|--------------------------------------------------------------------------
| Admin Line of Business Module (isolated: /admin/line-of-business/*)
|--------------------------------------------------------------------------
*/
Route::prefix('admin/line-of-business')->middleware('adminAuth')->namespace('App\Modules\AdminLineOfBusiness\Http\Controllers')->group(function () {
    Route::get('/dropdowns', [\App\Modules\AdminLineOfBusiness\Http\Controllers\AdminLineOfBusinessController::class, 'dropdowns']);
    Route::get('/', [\App\Modules\AdminLineOfBusiness\Http\Controllers\AdminLineOfBusinessController::class, 'index']);
    Route::post('/', [\App\Modules\AdminLineOfBusiness\Http\Controllers\AdminLineOfBusinessController::class, 'store']);
    Route::get('/{id}', [\App\Modules\AdminLineOfBusiness\Http\Controllers\AdminLineOfBusinessController::class, 'show']);
    Route::put('/{id}', [\App\Modules\AdminLineOfBusiness\Http\Controllers\AdminLineOfBusinessController::class, 'update']);
    Route::delete('/{id}', [\App\Modules\AdminLineOfBusiness\Http\Controllers\AdminLineOfBusinessController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Admin Features Module (isolated: /admin/features/*)
|--------------------------------------------------------------------------
*/
Route::prefix('admin/features')->middleware('adminAuth')->namespace('App\Modules\AdminFeature\Http\Controllers')->group(function () {
    Route::get('/dropdowns', [\App\Modules\AdminFeature\Http\Controllers\AdminFeatureController::class, 'dropdowns']);
    Route::get('/', [\App\Modules\AdminFeature\Http\Controllers\AdminFeatureController::class, 'index']);
    Route::post('/', [\App\Modules\AdminFeature\Http\Controllers\AdminFeatureController::class, 'store']);
    Route::get('/{id}', [\App\Modules\AdminFeature\Http\Controllers\AdminFeatureController::class, 'show']);
    Route::put('/{id}', [\App\Modules\AdminFeature\Http\Controllers\AdminFeatureController::class, 'update']);
    Route::delete('/{id}', [\App\Modules\AdminFeature\Http\Controllers\AdminFeatureController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Admin Payment Methods (platform: /admin/payment-methods/*)
|--------------------------------------------------------------------------
*/
Route::prefix('admin/payment-methods')->middleware('adminAuth')->group(function () {
    Route::get('/', [\App\Modules\AdminPaymentMethod\Http\Controllers\AdminPaymentMethodController::class, 'index']);
    Route::post('/', [\App\Modules\AdminPaymentMethod\Http\Controllers\AdminPaymentMethodController::class, 'store']);
    Route::get('/{id}', [\App\Modules\AdminPaymentMethod\Http\Controllers\AdminPaymentMethodController::class, 'show']);
    Route::put('/{id}', [\App\Modules\AdminPaymentMethod\Http\Controllers\AdminPaymentMethodController::class, 'update']);
    Route::delete('/{id}', [\App\Modules\AdminPaymentMethod\Http\Controllers\AdminPaymentMethodController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| V2 identity (JWT) — feature-flagged: USE_V2_AUTH in .env, config auth_v2
|--------------------------------------------------------------------------
*/
Route::prefix('v2')->middleware('v2.auth')->group(function () {
    Route::post('auth/login', [AuthControllerV2::class, 'login']);
    Route::middleware(['auth:jwt', 'v2.jwt_tenant'])->get('auth/me', [AuthControllerV2::class, 'me']);
});
