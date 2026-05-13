<?php

namespace App\Http\Controllers\API;

use App\AppEvents;
use App\Http\Controllers\Controller;
use App\UserDetail;
use App\OrderDetail;
use App\UserLogin;
use App\OTP;
use App\UserCompanies;
use App\CompanyDetail;
use App\CompanyAppointments;
use Log;
use DB;
use App\NotificationDB;
use App\NotificationRecipient;
use App\UserAccesPermissions;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use App\Notifications\NormalNotification;
use App\Notifications\BulkNotification;
use App\Notifications\MobileVerification;
use Twilio\Rest\Client;
use Carbon\Carbon;
use App\Jobs\SendEmail;

use function GuzzleHttp\json_decode;

class NotificationController extends Controller
{
    //
    private $otpMessage = array(
        "Dear User, kindly use the one time password (OTP) ? to login to your smartr account. Donot share this with anyone - TheSmartr",
        "Dear User, Please provide the one time password (OTP) ? to merchant to add your account to his company - TheSmartr",
    );
    private $defaultOtpType = 0;
    public function __construct()
    { }
    /*-------------------------------------------------------------------------------------*/
    /*  HOME API
    /*-------------------------------------------------------------------------------------*/
    public function index(Request $request)
    {

        $orderDetail = OrderDetail::with(['company:company_id,company_name,country_id,company_logo', 'company.country:country_id,country_code,country_name,currency_id', 'company.country.currency:currency_code,currency_id'])


            ->where('order_id', 220600000020)
            ->where('order_status', 'CP')
            ->first();
        $query_dump = DB::getQueryLog();
        return response()->json($orderDetail);
        return view('home');
    }
    /*-------------------------------------------------------------------------------------*/
    /*  USER NOTIFICATION LIST 
    /*-------------------------------------------------------------------------------------*/
    public function list(Request $request)
    {

        $data = $request->all();
        $validator = Validator::make($data, [
            'user_id' => 'required|integer',
            'app_type' => 'required|string'
        ]);

        if ($validator->fails()) {

            return response(['error' => $validator->errors(), 'Validation Error']);
        }
        try {

            $query = DB::table('notifications')
                ->join('notification_recipient', 'notifications.id', '=', 'notification_recipient.notification_id')
                ->join('notification_publisher', 'notifications.id', '=', 'notification_publisher.notification_id')
                ->join('app_events', 'notifications.event_id', '=', 'app_events.id');

            if ($request->has('company_id')) {
                $query = $query->where('notification_recipient.company_id', '=', $data['company_id']);
            }

            // Update all the notification as seen 
            NotificationRecipient::where('seen', 0)
                ->where('user_id', $data['user_id'])
                ->where('app_type', $data['app_type'])
                ->update(['seen' => 1]);


            $notificaion = $query->where('notification_recipient.user_id', '=', $data['user_id'])
                ->where('notification_recipient.app_type', '=', $data['app_type'])
                ->select(
                    'notifications.id as notification_id',
                    'app_events.id as event_id',
                    'app_events.name as event_name',
                    'notifications.message',
                    'notifications.redirect',
                    'notifications.payload',
                    'notification_recipient.sent_on',
                    'notification_recipient.read',
                    'notification_recipient.seen',
                    'notification_recipient.read_on',
                    'notification_recipient.user_id as reciver_id',
                    'notification_publisher.user_id as publisher_id',
                    'notification_publisher.company_id as company_id'
                )
                ->orderBy('notification_recipient.sent_on', 'DESC')
                ->get();


            // $notificaion = NotificationDB::whereHas('recipients', function ($query) use ($data) {
            //     $query->where('user_id', $data['user_id'])
            //         ->where('company_id', $data['company_id'])
            //         ->where('app_type', $data['app_type']);
            // })->publisher()->get();

            $unreadNotificaitonCount = $notificaion->where('read', 0)->count();
            $unseenNotificaitonCount = $notificaion->where('seen', 0)->count();
            $totalNotificationCount = $notificaion->count();

            // Conveerting payload to an array 
            $notificaion->map(function ($data) {
                $data->payload = json_decode($data->payload, true);
                $data->payload = (array) $data->payload;
                return $data;
            });
            $responseData = [
                'total' => $totalNotificationCount,
                'unseen' => $unseenNotificaitonCount,
                'unread' => $unreadNotificaitonCount,
                'notification' => $notificaion
            ];

            return response()->json($responseData, 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['error' => 'Something went wrong'], 401);
        }
    }
    public function count(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'user_id' => 'required|integer',
            'app_type' => 'required|string'
        ]);

        if ($validator->fails()) {

            return response(['error' => $validator->errors(), 'Validation Error']);
        }
        try {
            $notificationCount = NotificationRecipient::where('seen', 0)
                ->where('user_id', $data['user_id'])
                ->where('app_type', $data['app_type'])
                ->count();

            $responseData = [
                // 'error'=>false,
                'notitificationCount' => $notificationCount
            ];

            return response()->json($responseData, 200);
        } catch (Exception $ex) {
            Log::error($ex->getMessage());
            return response()->json(['error' => 'Something went wrong'], 401);
        }
    }


    /*-------------------------------------------------------------------------------------*/
    /*  NOTIFICATION READ 
    /*-------------------------------------------------------------------------------------*/
    public function read(Request $request)
    {
        $data = $request->all();
        $validator = Validator::make($data, [
            'company_id' => 'required|integer',
            'user_id' => 'required|integer',
            'notification_id' => 'required|integer',


        ]);
        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }

        $mytime = Carbon::now();
        $now = $mytime->toDateTimeString();
        NotificationRecipient::where('notification_id', $data['notification_id'])
            ->where('user_id', $data['user_id'])
            ->update(['read' => 1, 'read_on' => $now]);

        return response()->json(['message' => 'Updated notification succesfully'], 200);
    }
    /*-------------------------------------------------------------------------------------*/
    /*  SEND NORMAL / SIMPLE  NOFITICATIONS
    /*-------------------------------------------------------------------------------------*/
    public function sendNormalNotification(Request $request)
    {

        $data = $request->all();
        $validator = Validator::make($data, [
            'event' => 'required|string|max:255',
            'referenceId' => 'required'
        ]);
        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }

        // Get event details

        $event = strtolower($data['event']);
        $eventDetail = AppEvents::where('name', $event)->where('isActive', 1)->first();

        switch ($eventDetail->id) {
            case 1: // order/confirmed - event 

                // Get Order details 
                $orderDetail = OrderDetail::with(['company:company_id,company_name,country_id,company_logo', 'company.country:country_id,country_code,country_name,currency_id', 'company.country.currency:currency_code,currency_id'])
                    ->where('order_id', $data['referenceId'])
                    ->where('order_status', 'CP')
                    ->first();

                if (empty($orderDetail)) {
                    throw Exception('No data found for reference number');
                }
                // Get templates
                $title = 'Order #:OrderId has been confirmed';
                // Define the template message
                $businessMessage = 'Order Confirmed - Order successfully placed for  :CustomerName   with reference Order Id# :OrderId at :OrderDateTime';
                $customerMessage = 'Order Confirmed - Your order at :Company has been confirmed, with referce to Order Id#:OrderId';

                if (empty($orderDetail['executive_id'])) {
                    $businessMessage = 'Order Confirmed - :CustomerName   has placed an order  with reference Order Id#:OrderId at :OrderDateTime';
                    $customerMessage = 'Order Confirmed: Your order at :Company has been confirmed, with referce to Order Id#:OrderDateTime';
                }
                // Get Customer Details
                $customerDetail = UserDetail::where('user_id', $orderDetail['customer_id'])->first();

                $displayData = [
                    'Company' => ucfirst(html_entity_decode($orderDetail['company']['company_name'])),
                    'CustomerName' => ucfirst(html_entity_decode($customerDetail['first_name'])),
                    'OrderId' => $orderDetail['order_id'],
                    'OrderDateTime' => 'Dt:' . $orderDetail['order_date'] . ' Tm:' . $orderDetail['order_time']
                ];

                $businessMessage = $this->templateToMessage($businessMessage, $displayData);
                $customerMessage = $this->templateToMessage($customerMessage, $displayData);
                $title = $this->templateToMessage($title, $displayData);

                // Create Order url
                $url = 'order/details/' . $orderDetail['order_id'];

                // Make array and send notification
                $notifyData = [
                    'event' => $eventDetail->id,
                    'message' => $customerMessage,
                    'title' => $title,
                    'redirect' => true,
                    'appType' => 'customer',
                    'redirectURL' => $url,
                    'payload' => $orderDetail->toArray(),
                    'companyId' => $orderDetail['company_id'],
                    'branchId' => $orderDetail['branch_id'],
                    'senderId' => $orderDetail['executive_id'] ?? $orderDetail['customer_id'],
                    'recipients' => array(
                        array('user_id' => $orderDetail['customer_id'])
                    ),
                    'fcmTokens' => $this->getUserFirebaseToken($orderDetail['customer_id']),
                    'authentication_key' => config('larafirebase.customer_authentication_key')

                ];

                // Send notification to customer app
                Notification::send(null, new NormalNotification($notifyData));


                //----------------------------------------//
                // Send notification to business app //
                //----------------------------------------//

                // Get All company executives of the ordered company 
                $companyId = $orderDetail['company_id'];


                $executives = UserLogin::select('user_id', 'user_mobile', 'fcm_key')
                    ->whereHas('companies', function ($query) use ($companyId) {
                        $query->select('user_id')->where('company_id', $companyId)
                            ->where('user_type', [4, 3])
                            ->where('status', 1);
                    })
                    ->whereHas('access', function ($query) use ($companyId) {
                        $query->select('user_id')
                            ->where('module_id', 36)
                            ->where('Access_priv', 'Y');
                    })
                    ->get();

                if (empty($executives)) {
                    Log::error('Comapny: ' . $companyId . ' has no user to send notification to');
                    break;
                }
                // Fetch firebase keys
                $fcmTokens = $executives->whereNotNull('fcm_key')
                    ->where('fcm_key', '!=', '')
                    ->pluck('fcm_key')
                    ->all();
                $notifyData['appType'] = 'business';
                $notifyData['authentication_key'] = config('larafirebase.business_authentication_key');
                $notifyData['recipients'] = $executives;
                $notifyData['fcmTokens'] = $fcmTokens;
                $notifyData['message'] = $businessMessage;

                Notification::send(null, new NormalNotification($notifyData));

                break;
            case 2: // Event : appointment/confirmed 
            case 3: // Event : appointment/changed 
            case 4: // Event : appointment/completed 
            case 5: // Event : appointment/occupied 
            case 7: // Event : appointment/requested
            case 8: // Event : appointment/rejected
            case 6: // Event : appointment/cancelled 

                // Get appointment details 
                $appointment = CompanyAppointments::with(['company:company_id,company_name,country_id,company_logo', 'company.country:country_id,country_code,country_name,currency_id', 'company.country.currency:currency_code,currency_id'])
                    ->where('appointment_id', $data['referenceId'])
                    ->first();

                if (empty($appointment)) {
                    throw Exception('No data found for reference number');
                }
                $templates = $this->notificationTemplate($eventDetail->id);
                // Get templates
                $title = $templates['title'];
                $businessMessage = $templates['business'];
                $customerMessage = $templates['customer'];



                // Get Customer Details
                $customerDetail = UserDetail::where('user_id', $appointment['user_id'])->first();

                $displayData = [
                    'Company' => ucfirst(html_entity_decode($appointment['company']['company_name'])),
                    'CustomerName' => ucfirst(html_entity_decode($customerDetail['first_name'])),
                    'ReservationId' => $appointment['appointment_no'],
                    'OrderDateTime' => 'Dt:' . Carbon::parse($appointment['start'])->format('d-M-Y') . ' Tm:' . Carbon::parse($appointment['time'])->format('d-M-Y')
                ];

                $businessMessage = $this->templateToMessage($businessMessage, $displayData);
                $customerMessage = $this->templateToMessage($customerMessage, $displayData);
                $title = $this->templateToMessage($title, $displayData);

                // Make array and send notification
                $notifyData = [
                    'event' => $eventDetail->id,
                    'message' => $customerMessage,
                    'title' => $title,
                    'redirect' => true,
                    'appType' => 'customer',
                    'payload' => $appointment->toArray(),
                    'companyId' => $appointment['company_id'],
                    'branchId' => $appointment['branch_id'],
                    'senderId' => $appointment['created_by'] ?? $appointment['user_id'],
                    'recipients' => array(
                        array('user_id' => $appointment['user_id'])
                    ),
                    'fcm_token' => $this->getUserFirebaseToken($appointment['user_id']),
                    'authentication_key' => config('larafirebase.customer_authentication_key')

                ];

                // Send notification to customer app
                Notification::send(null, new NormalNotification($notifyData));

                //----------------------------------------//
                // Send notification to business app //
                //----------------------------------------//
                // Get All company executives of the ordered company 
                $companyId = $appointment['company_id'];
                $executives = UserLogin::select('user_id', 'user_mobile', 'fcm_key')
                    ->whereHas('companies', function ($query) use ($companyId) {
                        $query->where('company_id', $companyId)
                            ->where('user_type', [4, 3])
                            ->where('status', 1);
                    })
                    ->whereHas('access', function ($query) use ($companyId) {
                        $query->select('user_id')
                            ->where('module_id', 36)
                            ->where('Access_priv', 'Y');
                    })
                    ->get();

                if (empty($executives)) {
                    Log::error('Comapny: ' . $companyId . ' has no user to send notification to');
                    break;
                }
                // Fetch firebase keys
                $fcmTokens = $executives->whereNotNull('fcm_key')
                    ->where('fcm_key', '!=', '')
                    ->pluck('fcm_key')
                    ->all();
                $notifyData['appType'] = 'business';
                $notifyData['recipients'] = $executives;
                $notifyData['fcmTokens'] = $fcmTokens;
                $notifyData['message'] = $businessMessage;
                $notifyData['authentication_key'] = config('larafirebase.business_authentication_key');
                Notification::send(null, new NormalNotification($notifyData));
                break;

            case 9: // BROADCAST PROMOTIONAL MESSAGE TO CUSTOMER
                //----------------------------------------//
                // Send notification to Customer's app //
                //----------------------------------------//

                // Get all the Users of a company with thier FCM Key,PhoneNumber and EmailId
                $companyId = $data['referenceId'];
                $recipients = UserLogin::select('user_id', 'user_mobile', 'fcm_key')
                    ->whereHas('companies', function ($query) use ($companyId) {
                        $query->select('user_id')->where('company_id', $companyId)->whereIn('user_type', [4, 5])
                            ->where('status', 1);
                    })
                    ->get();

                $keys = $recipients->whereNotNull('fcm_key')->where('fcm_key', '!=', '')->pluck('fcm_key')->all();
                $userMobileNumber = $recipients->pluck('user_mobile')->all();

                $notifyData = [
                    'event' => $eventDetail->id,
                    'message' => $data['message'],
                    'title' => $data['title'],
                    'redirect' => false,
                    'appType' => 'customer',
                    'redirectURL' => '',
                    'payload' => array(
                        'title' => $data['title'],
                        'message' => $data['message'],
                        'compayId' => $data['referenceId']
                    ),
                    'companyId' => $data['referenceId'],
                    'branchId' => '',
                    'senderId' => $data['senderId'],
                    'recipients' => $recipients,
                    'fcmTokens' => $keys,
                    'authentication_key' => config('larafirebase.customer_authentication_key')

                ];

                // if (in_array(2, $data['channels'])) {
                foreach ($userMobileNumber as $user) {

                    $this->sendMessage($data["message"], "+1" . $user);
                }
                // } else if (in_array(1, $data['channels'])) {
                // Select the prefered channels 
                $channels = array('push', 'database');
                // Send Notifications

                Notification::send(null, new BulkNotification($channels, $notifyData));
                // }
                return response()->json([
                    'error_flag' => false,
                    'nessage' => 'Campain sent successfully'
                ]);
            default:
                break;
        }
        return response()->json(['message' => 'notification treiggered successfully'], 200);
    }
    /*-------------------------------------------------------------------------------------*/
    /*  AUTHENTICATION NOTIFICATION (ONE TIME PASSWORD)
    /*-------------------------------------------------------------------------------------*/
    public function sendOtp(Request $request)
    {

        $data = $request->all();
        $validator = Validator::make($data, [
            'phoneno' => 'required|integer',
        ]);
        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }
        $phoneNo = $data['phoneno'];
        $type = $data['type'] ?? $this->defaultOtpType; // 1 - Normal OTP 2 - OTP to Add Customer 
        $otp = rand(1000, 9999);    // Generate a Random OTP
        $isUserRegistered = true;
        $login = new UserLogin();
        $user = $login->select('user_id', 'user_mobile')
            ->where('user_mobile', $phoneNo)
            ->first();
       
        // Add OTP to DB
        $userOtp = OTP::updateOrCreate(
            ['user_mobile' => $phoneNo],
            ['secret_code' => $otp, 'status' => 0]

        );
        if (empty($user)) {
            $userOtp->setPhoneNumberAttribute($phoneNo);
           
            $isUserRegistered = false;
        }
        $content = Str::replaceArray('?', [$otp], $this->otpMessage[$type]);

        Notification::send($userOtp, new MobileVerification($content));
        return response()->json(array('error'=>false,'message'=>'One Time Password sent'));
    }
    /*-------------------------------------------------------------------------------------*/
    /*  NOTIFICATION TEMPLATE
    /*-------------------------------------------------------------------------------------*/
    private function notificationTemplate($event)
    {
        switch ($event) {
            case 2: // appointment/confirmed
                $template = [
                    'title' => 'Reservation #:ReservationId has been confirmed',
                    'business' => 'Reservation Confirmed - Reservation for :CustomerName   has been confirmed with reference to  Reference Id# :ReservationId on :OrderDateTime',
                    'customer' => 'Reservation Confirmed - Your reservation at :Company has been confirmed, with referce to Reservation Id#:ReservationId on :OrderDateTime'
                ];
                break;
            case 3: // appointment/changed
                $template = [
                    'title' => 'Reservation #:ReservationId has been changed',
                    'business' => 'Reservation Updated - Reservation for :CustomerName   has been changed with reference to  Reference Id# :ReservationId on :OrderDateTime',
                    'customer' => 'Reservation Updated - Your reservation at :Company has been changed, with referce to Reservation Id#:ReservationId on :OrderDateTime'
                ];
                break;
            case 4: // appointment/completed
                $template = [
                    'title' => 'Reservation #:ReservationId has been completed',
                    'business' => 'Reservation Completed - Reservation for :CustomerName   has been completed with reference to  Reference Id# :ReservationId on :OrderDateTime and resoyrce has been released',
                    'customer' => 'Reservation Completed - Your reservation #:ReservationId at :Company has been successfully completed, Thank you !!!'
                ];
                break;
            case 5: // appointment/occupied
                $template = [
                    'title' => 'Reservation #:ReservationId has been occupied',
                    'business' => 'Reservation Occupied - :CustomerName has occupied the reservation #:ReservationId',
                    'customer' => 'Reservation Occupied - Your have occupied you reservation at :Company  with referce to Reservation Id#:ReservationId'
                ];
                break;
            case 6: // appointment/canceled
                $template = [
                    'title' => 'Reservation #:ReservationId cancelled',
                    'business' => 'Reservation Cancelled - Reservation  cancelled for :CustomerName   with reference to  Reference Id# :ReservationId on :OrderDateTime',
                    'customer' => 'Reservation Cancelled - Your reservation #:ReservationId at :Company has been cancelled'
                ];
                break;
            case 7: // appointment/requested
                $template = [
                    'title' => 'Reservation Requested',
                    'business' => 'Reservation Requested - :CustomerName  has a reservation with  Reference Id# :ReservationId',
                    'customer' => 'Reservation Requested - Your reservation at :Company has been sent to the company, with referce to Reservation Id#:ReservationId on :OrderDateTime . Kindly wait for thier approval'
                ];
                break;
            case 8:
                $template = [
                    'title' => 'Reservation #:ReservationId Rejected',
                    'business' => 'Reservation Rejected - :CustomerName  reservation with  Reference Id# :ReservationId has been rejected',
                    'customer' => 'Reservation Rejected - Sorry !!! Your reservation at :Company with referce to Reservation Id#:ReservationId on :OrderDateTime has been rejected'
                ];
                break;
            default:
                $template = array();
                break;
        }
        return $template;
    }
    /*-------------------------------------------------------------------------------------*/
    /*  TEMPLATE TO MESSGAE - CONVERSION
    /*-------------------------------------------------------------------------------------*/
    private function templateToMessage($template, $templateData)
    {
        $message  =  __($template, $templateData);
        return $message;
    }
    /*-------------------------------------------------------------------------------------*/
    /*  FETCH USER FIREBASE TOKEN 
    /*-------------------------------------------------------------------------------------*/
    private function getUserFirebaseToken($userId)
    {
        // User firebase key's 
        $fcmTokens = UserLogin::whereNotNull('fcm_key')->where('fcm_key', '!=', '')->where('user_id', $userId)->pluck('fcm_key')->toArray();
        if (empty($fcmTokens)) {
            return NULL;
        }
        return $fcmTokens;
    }

    public function bulk(Request $request)
    {
        $inputData = $request->all();

        $validator = Validator::make($inputData, [
            'json_data' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Validation Error']);
        }
        $data = $this->myDecrypt($inputData['json_data']);

        $validator = Validator::make($data, [
            'company_id' => 'required|integer',
            'broadcast_message' => 'required|string',
            'loggedin_user_id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Invalid input details']);
        }
        $request->request->add([
            'event' => 'broadcast/promotional',
            'referenceId' => $data['company_id'],
            'title' => 'Promotional Message',
            'message' => $data['broadcast_message'],
            'channels' => json_decode($data['channels']),
            'senderId' => $data['loggedin_user_id']

        ]);

        $this->sendNormalNotification($request);
    }
    public function bulkList(Request $request)
    {
        $inputData = $request->all();

        $validator = Validator::make($inputData, [
            'json_data' => 'required|string',
        ]);

        if ($validator->fails()) {

            return response(['error' => $validator->errors(), 'Validation Error']);
        }
        $data = $this->myDecrypt($inputData['json_data']);

        $validator = Validator::make($data, [
            'company_id' => 'required|integer',
            'loggedin_user_id' => 'required|integer',
            'module_id' => 'required|integer'
        ]);
        if ($validator->fails()) {
            return response(['error' => $validator->errors(), 'Invalid input details']);
        }
        $companyId = $data['company_id'];
        $notifications = NotificationDB::whereHas('publisher', function ($query) use ($companyId) {
            $query->where('company_id', $companyId);
        })
            ->where('event_id', 9)
            ->get();
        $companyNotifications = array();
        if (!empty($notifications)) {
            foreach ($notifications as $notification) {
                $companyNotifications[] = array(
                    'broadcast_id' => $notification['id'],
                    'broadcast_message' => $notification['message'],
                    'company_id' => $companyId,
                    'broadcast_type' => 'P',
                    'created_dtm' => $notification['created_at']
                );
            }
        }

        // User Module access 
        $access = UserAccesPermissions::where('user_id', $data['loggedin_user_id'])->where('module_id', $data['module_id'])->first();

        $response = array(
            'broad' => $companyNotifications,
            'user_access' => $access,
            'error_flag' => false,
            'message' => ''
        );
        return response()->json($response, 200);
    }
    private function myDecrypt(String $data)
    {
        $secretKey = "tpcgkCABsh051409";
        $method = "AES-128-ECB";
        $decryptedData = openssl_decrypt(base64_decode($data), $method, $secretKey, OPENSSL_RAW_DATA);
        return json_decode($decryptedData, true);
    }
    private function sendMessage($message, $recipients)
    {
        $account_sid = env("TWILIO_ACCOUNT_SID");
        $auth_token = env("TWILIO_AUTH_TOKEN");
        $twilio_number = env("TWILIO_FROM");
        $client = new Client($account_sid, $auth_token);
        $message =  $client->messages->create(
            $recipients,
            ['from' => $twilio_number, 'body' => $message, 'statusCallback' => 'https://eoztdcjs8519rr.m.pipedream.net']
        );
    }
    public function enqueue(Request $request)
    {
        $details = ['email' => 'nizamuddin@tutorialspoint.com'];
        SendEmail::dispatch($details);
    }
}
