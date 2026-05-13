<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\AttendanceDetail;
use App\CompanyDetail;
use App\UserFlexibleTimings;
use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Requests\AttendanceRequest;
use App\Http\Requests\AttendancePunchinRequest;
use App\Http\Responses\AttendanceResponse;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Log;
use Illuminate\Support\Facades\DB;

class AttendanceController extends Controller
{

    protected $punch_buffer_time = 30; // 30 minutes 
    protected $timezone = 'Asia/Kolkata'; // Set timezone explicitly to Asia/Kolkata

    // Punch In API
    public function punchIn(AttendancePunchinRequest $request)
    {
        try {
            $data = $request->validated();
            if ($data['user_id'] == 6) {
                $invoice = new InvoiceController;
                if ($trialCheck = $invoice->checkTrial(32)) {
                    return $trialCheck; // This ends the function if there's an error
                }
            }
            // Check if already punched in
            $existing = AttendanceDetail::where('user_id', $data['user_id'])
                ->whereNull('punch_out')
                ->first();

            // Validate the punch In time 
            $punch_time = $this->validatePunch($data);

            if ($existing) {
                return AttendanceResponse::error('Already punched in, please punch out first', 400);
            }

            $attendance = AttendanceDetail::create([
                'user_id' => $data['user_id'],
                'branch_id' => $data['branch_id'],
                'punch_in' => $punch_time,
                'latitude' => $data['latitude'],
                'longitude' => $data['longitude'],
            ]);
            return AttendanceResponse::success($attendance, 'Punched in successfully');
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return AttendanceResponse::error($e->getMessage(), 300);
        }
    }

    // Punch Out API
    public function punchOut(AttendanceRequest $request)
    {
        try {
            $data = $request->validated();
            if ($data['user_id'] == 6) {
                $invoice = new InvoiceController;
                if ($trialCheck = $invoice->checkTrial(32)) {
                    return $trialCheck; // This ends the function if there's an error
                }
            }
            // Current time in the specified timezone
            $punch_out = Carbon::now($this->timezone)->format('Y-m-d H:i:s');
            $attendance = AttendanceDetail::where('user_id', $data['user_id'])
                ->whereNull('punch_out')
                ->first();
            if (!$attendance) {
                return AttendanceResponse::error('No punch-in record found', 404);
            }
            $attendance->update(['punch_out' => $punch_out]);
            return AttendanceResponse::success($attendance, 'Punched out successfully');
        } catch (Exception $e) {
            return AttendanceResponse::error($e->getMessage(), 500);
        }
    }

    // Check Attendance Status API
    public function checkAttendance(AttendanceRequest $request)
    {

        try {
            $data = $request->validated();
            // $invoice = new InvoiceController;
            // if ($trialCheck = $invoice->checkTrial($data['company_id'])) {
            //     return $trialCheck; // This ends the function if there's an error
            // }
            $attendance = AttendanceDetail::where('user_id', $data['user_id'])
                ->whereNull('punch_out')
                ->first();

            // Check if user punch is greater less than the current date 
            if ($attendance) {
                // Parse punch_in as a Carbon instance
                $punchInDate = Carbon::parse($attendance->punch_in)->startOfDay();
                $currentDate = Carbon::now()->startOfDay();

                // Compare punch_in date with the current date
                if ($punchInDate->lessThan($currentDate)) {
                    // Auto punch out: Set punch_out to "00:00:00" on the current date
                    DB::transaction(function () use ($attendance, $punchInDate) {
                        $punchOutDateTime = $punchInDate->copy()->addDay()->setTime(0, 0, 0); // Next day at "00:00:00"
                        $attendance->punch_out = $punchOutDateTime; // Set punch_out to the calculated date and time
                        $attendance->remarks = 'Auto-adjusted by system';
                        $attendance->save();
                    });
                    unset($attendance);
                }
            }

            if ($attendance) {
                return AttendanceResponse::success($attendance, 'User already punchedin');
            }
            return AttendanceResponse::success([], 'User is not punched in');
        } catch (Exception $e) {
            $http_status_code = (int)$e->getCode();
            return AttendanceResponse::error($e->getMessage(), 300);
        }
    }

    // Validate punch 
    private function validatePunch($data)
    {
        $company = CompanyDetail::select('company_dawn as opening', 'company_dusk as closing', 'latitude', 'longitude', 'radius')
            ->where('company_id', $data['company_id'])->first();

        // Company timings
        $openingTime = $company['opening']; // Stored opening time
        $closingTime =  $company['closing']; // Stored closing time
        $bufferMinutes = $this->punch_buffer_time;       // Buffer time in minutes (can be dynamic)


        // Input location
        // $input_latitude = $data['latitude'];
        // $input_longitude = $data['longitude'];
        // $isWithin = $this->isWithinRadius($company['latitude'], $company['longitude'], $input_latitude, $input_longitude, $company['radius']);
        // if (!$isWithin) {
        //     throw new Exception('Punch not allowed 2 outside company location');
        // }


        // Today's date
        $todayDate = Carbon::today($this->timezone);
        $tomorrowDate = $todayDate->copy()->addDay();

        // Adjust opening and closing times with buffer
        $adjustedOpeningTime = $todayDate->copy()
            ->setTimeFromTimeString($openingTime)
            ->subMinutes($bufferMinutes); // Subtract buffer from opening time

        // Adjust closing time (tomorrow if past midnight)
        $adjustedClosingTime = $closingTime > $openingTime
            ? $todayDate->copy()->setTimeFromTimeString($closingTime)->addMinutes($bufferMinutes)
            : $tomorrowDate->copy()->setTimeFromTimeString($closingTime)->addMinutes($bufferMinutes);
        // Current time in the specified timezone
        $currentDateTime = Carbon::now($this->timezone);

        $isAttendanceTimingFlexible = UserFlexibleTimings::where('user_id', $data['user_id'])
            ->where('flexible_timing_status', 'A')
            ->first();
        if ($isAttendanceTimingFlexible) {
            return $currentDateTime;
        }
        // Check conditions
        if ($currentDateTime->lessThan($adjustedOpeningTime)) {
            throw new Exception("Check-in allowed only at or after " . $adjustedOpeningTime->toDateTimeString());
        }
        if ($currentDateTime->greaterThan($adjustedClosingTime)) {
            throw new Exception("Check-in not allowed after " . $adjustedClosingTime->toDateTimeString());
        }
        return  $currentDateTime->format('Y-m-d H:i:s');
    }



    // Analytics API
    public function analytics(AttendanceRequest $request)
    {
        $attendances = AttendanceDetail::selectRaw('DATE(punch_in) as date, COUNT(*) as punches')
            ->groupBy('date')
            ->get();

        return response()->json(['data' => $attendances]);
    }

    // Export to Excel
    public function exportExcel(AttendanceRequest $request)
    {
        $attendances = AttendanceDetail::all();

        $headers = [
            "Content-type" => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=attendance.xls",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
        ];

        $columns = ['User ID', 'Punch In', 'Punch Out', 'Latitude', 'Longitude'];

        $callback = function () use ($attendances, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($attendances as $attendance) {
                fputcsv($file, [
                    $attendance->user_id,
                    $attendance->punch_in,
                    $attendance->punch_out,
                    $attendance->latitude,
                    $attendance->longitude,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    private function isWithinRadius($lat1, $lon1, $lat2, $lon2, $radius)
    {
        // Earth's radius in meters (6371 km)
        $earthRadius = 6371000;

        // Convert degrees to radians
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);

        // Calculate differences
        $deltaLat = $lat2 - $lat1;
        $deltaLon = $lon2 - $lon1;

        // Haversine formula
        $a = sin($deltaLat / 2) ** 2 +
            cos($lat1) * cos($lat2) * sin($deltaLon / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        $distance = $earthRadius * $c;

        // Check if the distance is within the radius
        return $distance <= $radius;
    }


    public function getMonthlyAttendance(Request $request)
    {
        // Validate input
        $request->validate([
            'user_id' => 'required|integer',
        ]);

        $userId = $request->input('user_id');

        // if ($userId == 6) {
        //     $invoice = new InvoiceController;
        //     if ($trialCheck = $invoice->checkTrial(32)) {
        //         return $trialCheck; // This ends the function if there's an error
        //     }
        // }
        // Get the current month and year
        $currentDate = Carbon::now();

        $currentMonth = $currentDate->month;
        $currentYear = $currentDate->year;

        // Define company opening and closing times (adjust as needed)
        $companyOpening = '09:00:00'; // Example: 9:00 AM
        $companyClosing = '18:00:00'; // Example: 6:00 PM
        $bufferTime = 30; // Buffer in minutes
        $endOfMonth = '';
        // Generate list of all dates in the current month
        $startOfMonth = $currentDate->copy()->startOfMonth();


        $endOfMonth = $currentDate->copy()->endOfMonth();

        $period = [];
        $current = $startOfMonth->copy();

        while ($current->lte($endOfMonth)) {
            $period[] = $current->toDateString();
            $current->addDay();
        }




        // Fetch attendance records for the current month
        $attendanceRecords = DB::table('attendance_detail') // Replace 'attendance' with your table name
            ->where('user_id', $userId)
            ->whereYear('punch_in', '=', $currentYear)
            ->whereMonth('punch_in', '=', $currentMonth)
            ->groupBy(DB::raw('DATE(punch_in)'))
            ->selectRaw('
                DATE(punch_in) as date,
                MIN(punch_in) as first_punch_in,
                MAX(punch_out) as last_punch_out,
                SUM(TIMESTAMPDIFF(SECOND, punch_in, punch_out)) as total_seconds_present
            ')
            ->get()
            ->keyBy('date'); // Key the records by date for easy lookup


        $results = [];
        foreach ($period as $date) {
            if ($currentDate < $date) {
                continue;
            }

            $dayOfWeek = Carbon::parse($date)->dayOfWeek; // 0 = Sunday, 6 = Saturday

            // Check if attendance exists for this date
            $attendance = $attendanceRecords->get($date);

            if ($attendance) {
                // Calculate total effective hours
                $totalSeconds = $attendance->total_seconds_present ?? 0;
                $hours = floor($totalSeconds / 3600);
                $minutes = floor(($totalSeconds % 3600) / 60);
                $totalEffectiveHours = "{$hours} hours and {$minutes} minutes";

                // Determine if the punch-in was on-time or late
                $firstPunchIn = Carbon::parse($attendance->first_punch_in);
                $onTimeLimit = Carbon::parse($date . ' ' . $companyOpening)->addMinutes($bufferTime);

                $status = $firstPunchIn->lessThanOrEqualTo($onTimeLimit) ? 'On-time' : 'Late';

                $results[] = [
                    'date' => $date,
                    'status' => $status,
                    'first_punch_in' => $attendance->first_punch_in,
                    'last_punch_out' => $attendance->last_punch_out,
                    'total_effective_hours' => $totalEffectiveHours
                ];
            } else {
                // Mark as Off or Absent
                $status = ($dayOfWeek === 0 || $dayOfWeek === 6) ? 'Off' : 'Absent';

                $results[] = [
                    'date' => $date,
                    'status' => $status,
                    'first_punch_in' => null,
                    'last_punch_out' => null,
                    'total_effective_hours' => null,
                ];
            }
        }
        // Sort the results by date in descending order
        usort($results, function ($a, $b) {
            return strcmp($b['date'], $a['date']); // Compare in reverse order
        });

        return response()->json($results);
    }
}
