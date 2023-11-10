<?php

namespace App\Http\Controllers\Api\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\BloodBag;
use App\Models\Deferral;
use App\Models\Setting;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use DateTime;

class DashboardController extends Controller
{

 
    public function getDashboardStock()
    {
        $bloodBags = DB::table('user_details')
            ->leftJoin('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
            ->select('user_details.blood_type', 'blood_bags.serial_no', 'blood_bags.date_donated', 'bled_by', 'blood_bags.created_at') // Include 'created_at' in the select
            ->whereIn('user_details.blood_type', ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])
            ->where('blood_bags.isStored', '=', 1)
            ->where('blood_bags.isExpired', '=', '0')
            ->where('blood_bags.status', '=', '0')
            ->where('blood_bags.isUsed', '=', '0')
            ->get();
        
        $bloodTypes = ['A+', 'B+', 'O+', 'AB+', 'A-', 'B-', 'O-', 'AB-'];
        
        $result = [];
        $latestCreatedAt = null; // Initialize a variable to store the latest created_at value

        // Find the latest created_at value
        $latestCreatedAt = $bloodBags->max('created_at');
        
        // Format the latestCreatedAt
        $formattedLatestCreatedAt = $latestCreatedAt ? date('Y-m-d h:i A', strtotime($latestCreatedAt)) : null;
        if (!$formattedLatestCreatedAt || count($bloodBags) === 0) {
            // Set the formattedLatestCreatedAt to the current date and time
            $formattedLatestCreatedAt = date('Y-m-d h:i A');
        }
        foreach ($bloodTypes as $bloodType) {
            $bloodBagsCount = $bloodBags->where('blood_type', $bloodType)->count();

            $legend = '';

            if ($bloodBagsCount <= 0) {
                $legend = 'Empty';
            } elseif ($bloodBagsCount <= 11) {
                $legend = 'Critically low';
            } elseif ($bloodBagsCount <= 19) {
                $legend = 'Low';
            } elseif ($bloodBagsCount <= 99) {
                $legend = 'Normal';
            } else {
                $legend = 'High';
            }

            $totalBloodBagsCount = $bloodBags->count();

            $result[] = [
                'blood_type' => $bloodType,
                'status' => $bloodBagsCount > 0 ? 'Available' : 'Unavailable',
                'legend' => $legend,
                'count' => $bloodBagsCount,
            ];
        }

        return response()->json([
            'blood_bags' => $result,
            'latest_created_at' => $formattedLatestCreatedAt, // Return the formatted value
        ]);
    }



    public function getQuota()
    {
        $bloodBags = DB::table('user_details')
            ->leftJoin('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
            ->select('user_details.blood_type', 'blood_bags.serial_no', 'blood_bags.date_donated', 'bled_by')
            ->whereIn('user_details.blood_type', ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])
            ->where('blood_bags.isStored', '=', 1)
            ->where('blood_bags.isExpired', '=', '0')
            ->where('blood_bags.status', '=', '0')
            ->where('user_details.remarks', '=', '0')
            ->get();
    
        $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
    
        $settingsPerQuarter = Setting::where('setting_desc', 'quarter_quota')->first();
        $settingsPerMonth = Setting::where('setting_desc', 'monthly_quota')->first();
        $settingsPerWeek = Setting::where('setting_desc', 'weekly_quota')->first();
        $settingsPerDay = Setting::where('setting_desc', 'daily_quota')->first();

        $quotaPerQuarter = $settingsPerQuarter->setting_value;
        $quotaPerMonth = $settingsPerMonth->setting_value;
        $quotaPerWeek = $settingsPerWeek->setting_value;
        $quotaPerDay = $settingsPerDay->setting_value;


        $result = [];
    
        foreach ($bloodTypes as $bloodType) {
            $bloodBagsCount = $bloodBags->where('blood_type', $bloodType)->count();
            $quotaQuarter = $quotaPerQuarter / count($bloodTypes);
            $quotaMonth = $quotaPerMonth / count($bloodTypes);
            $quotaWeek = $quotaPerWeek / count($bloodTypes);
            $quotaDay = $quotaPerDay / count($bloodTypes);
            
            $availabilityPercentageQuarter = ($bloodBagsCount / $quotaQuarter) * 100;
            $availabilityPercentageMonth = ($bloodBagsCount / $quotaMonth) * 100;
            $availabilityPercentageWeek = ($bloodBagsCount / $quotaWeek) * 100;
            $availabilityPercentageDay = ($bloodBagsCount / $quotaDay) * 100;
            
            $bloodBagsQuantity = $bloodBags
                ->where('blood_type', $bloodType)
                ->pluck('serial_no')
                ->count();
            
            $legend = '';
            
            if ($bloodBagsCount <= 0) {
                $legend = 'Empty';
            } else {
                if ($availabilityPercentageQuarter <= 10) {
                    $legend = 'Critically low';
                } elseif ($availabilityPercentageQuarter <= 50) {
                    $legend = 'Low';
                } else {
                    $legend = 'Normal';
                }
            }
            
            $result[] = [
                'blood_type' => $bloodType,
                'status' => $bloodBagsCount > 0 ? 'Available' : 'Unavailable',
                'legend' => $legend,
                'percentage_quarter' => $availabilityPercentageQuarter,
                'percentage_month' => $availabilityPercentageMonth,
                'percentage_week' => $availabilityPercentageWeek,
                'percentage_day' => $availabilityPercentageDay,
                'quantity' => $bloodBagsQuantity,
            ];
        }
    
        return response()->json([
            'status' => 'success',
            'blood_bags' => $result,
        ]);
    }

   public function countBloodBagPerMonth(Request $request) {
        $currentYear = date('Y');
        $currentMonth = date('n');
        $monthCounts = [];

        $bloodType = $request->input('blood_type');

        if($bloodType == 'All'){
            for ($i = 1; $i <= $currentMonth; $i++) {
                $monthName = date('F', mktime(0, 0, 0, $i, 1));
                $startDate = date('Y-m-d', strtotime($currentYear.'-'.$i.'-01'));
                $endDate = date('Y-m-t', strtotime($currentYear.'-'.$i.'-01'));
                
                $bloodBags = DB::table('user_details')
                    ->leftJoin('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
                    ->select('user_details.blood_type', 'blood_bags.serial_no', 'blood_bags.date_donated', 'bled_by')
                    ->whereIn('user_details.blood_type', ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])
                    ->where('blood_bags.isStored', '=', 1)
                    ->where('blood_bags.isExpired', '=', '0')
                    ->where('blood_bags.status', '=', '0')
                    ->where('user_details.remarks', '=', '0')
                    ->whereYear('date_donated', $currentYear)
                    ->whereBetween('date_donated', [$startDate, $endDate])
                    ->count();
        
                $monthCounts[$monthName] = $bloodBags;
            }
        }else{
            for ($i = 1; $i <= $currentMonth; $i++) {
                $monthName = date('F', mktime(0, 0, 0, $i, 1));
                $startDate = date('Y-m-d', strtotime($currentYear.'-'.$i.'-01'));
                $endDate = date('Y-m-t', strtotime($currentYear.'-'.$i.'-01'));
                
                $bloodBags = DB::table('user_details')
                    ->leftJoin('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
                    ->select('user_details.blood_type', 'blood_bags.serial_no', 'blood_bags.date_donated', 'bled_by')
                    ->where('user_details.blood_type', $bloodType)
                    ->where('blood_bags.isStored', '=', 1)
                    ->where('blood_bags.isExpired', '=', '0')
                    ->where('blood_bags.status', '=', '0')
                    ->where('user_details.remarks', '=', '0')
                    ->whereYear('date_donated', $currentYear)
                    ->whereBetween('date_donated', [$startDate, $endDate])
                    ->count();
        
                $monthCounts[$monthName] = $bloodBags;
            }
        }
       
      // Retrieve the latest updated_at value
      $latestUpdatedAt = DB::table('user_details')
          ->leftJoin('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
          ->whereIn('user_details.blood_type', ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])
          ->where('blood_bags.isStored', '=', 1)
          ->where('blood_bags.isExpired', '=', '0')
          ->where('blood_bags.status', '=', '0')
          ->where('user_details.remarks', '=', '0')
          ->orderBy('blood_bags.created_at', 'desc')
          ->value('blood_bags.created_at');
      
      return response()->json([
          'status' => 'success',
          'month_counts' => array($monthCounts),
          'latest_date' => $latestUpdatedAt
      ]);
   }

    
    public function countDonorPerBarangay(Request $request) {
        $quarter = $request->input('quarter');
    
        $year = date('Y');

        $firstQuarterStart = $year . '-01-01';
        $firstQuarterEnd = $year . '-03-31';
    
        $secondQuarterStart = $year . '-04-01';
        $secondQuarterEnd = $year . '-06-30';
    
        $thirdQuarterStart = $year . '-07-01';
        $thirdQuarterEnd = $year . '-09-30';
    
        $fourthQuarterStart = $year . '-10-01';
        $fourthQuarterEnd = $year . '-12-31';
    
        if ($quarter === 'Q1') {
            $startDate = $firstQuarterStart;
            $endDate = $firstQuarterEnd;
        } elseif ($quarter === 'Q2') {
            $startDate = $secondQuarterStart;
            $endDate = $secondQuarterEnd;
        } elseif ($quarter === 'Q3') {
            $startDate = $thirdQuarterStart;
            $endDate = $thirdQuarterEnd;
        } elseif ($quarter === 'Q4') {
            $startDate = $fourthQuarterStart;
            $endDate = $fourthQuarterEnd;
        } elseif ($quarter === 'All') {
            $startDate = '1970-01-01'; 
            $endDate = date('2099-12-31'); 
        
        }
    
        $donorsPerBarangay = DB::table('user_details')
            ->leftJoin('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
            ->select(
                'user_details.barangay',
                DB::raw('count(distinct user_details.user_id) as donor_count'),
                DB::raw('MAX(blood_bags.created_at) as latest_date_donated')
            )
            ->where('blood_bags.isCollected', '=', 1)
            ->where('user_details.municipality', '=', 'CITY OF VALENZUELA')
            ->whereBetween('blood_bags.created_at', [$startDate, $endDate])
            ->groupBy('user_details.barangay')
            ->get();
    
        // Find the maximum date considering AM/PM
        $latestDate = $donorsPerBarangay->max(function ($donor) {
            return strtotime($donor->latest_date_donated);
        });
    
        // Format the maximum date to the desired 12-hour format
        $latestDate = date('Y-m-d h:i A', $latestDate);
    
        // Convert the latest_date_donated field in each result to 12-hour format
        $donorsPerBarangay->transform(function ($donor) {
            $donor->latest_date_donated = date('Y-m-d h:i A', strtotime($donor->latest_date_donated));
            return $donor;
        });
    
        return response()->json([
            'status' => 'success',
            'donors_per_barangay' => $donorsPerBarangay,
            'latest_date' => $latestDate,
        ]);
    }
    
    public function mbdQuickView(Request $request)
    {
        try {
            $month = $request->input('month');
            $year = $request->input('year');
            
            $data = [];

            $query = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
                ->join('galloners', 'user_details.user_id', '=', 'galloners.user_id')
                ->join('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
                ->where('user_details.remarks', 0)
                ->where('user_details.status', 0)
                ->where('galloners.donate_qty', '>', 0);

            $reactive = BloodBag::join('reactive_blood_bags as rbb', 'blood_bags.blood_bags_id', '=', 'rbb.blood_bags_id');
            $spoiled = BloodBag::join('spoiled_blood_bags as sbb', 'blood_bags.blood_bags_id', '=', 'sbb.blood_bags_id');

            // Apply month filter if a specific month is selected
            if ($month != 'All') {
                $query->whereMonth('blood_bags.date_donated', $month);
            }

            // Apply year filter if a specific year is selected
            if ($year != 'All') {
                $query->whereYear('blood_bags.date_donated', $year);

            }

            $totalDonors = $query->count();

            // Filter totalTempDeferral, totalPermaDeferral, totalDispensed, and totalExpired
            $totalTempDeferral = Deferral::where('deferral_type_id', '1')->where('user_id', '>', 0);
            $totalPermaDeferral = Deferral::where('deferral_type_id', '2')->where('user_id', '>', 0);
            $totalDispensed = BloodBag::where('isUsed', '1')->where('user_id', '>', 0);
            $totalExpired = BloodBag::where('isExpired', '1')->where('user_id', '>', 0);

            if ($month != 'All') {
                $totalTempDeferral->whereMonth('created_at', $month);
                $totalPermaDeferral->whereMonth('created_at', $month);
                $totalDispensed->whereMonth('dispensed_date', $month);
                $totalExpired->whereMonth('expiration_date', $month);
                $reactive->whereMonth('rbb.created_at', $month);
                $spoiled->whereMonth('sbb.created_at', $month);

            }

            if ($year != 'All') {
                $totalTempDeferral->whereYear('created_at', $year);
                $totalPermaDeferral->whereYear('created_at', $year);
                $totalDispensed->whereYear('dispensed_date', $year);
                $totalExpired->whereYear('expiration_date', $year);
                $reactive->whereYear('rbb.created_at', $year);
                $spoiled->whereYear('sbb.created_at', $year);

            }

            $totalTempDeferral = $totalTempDeferral->count();
            $totalPermaDeferral = $totalPermaDeferral->count();
            $totalDispensed = $totalDispensed->count();
            $totalExpired = $totalExpired->count();
            $totalReactiveBoodBag = $reactive->count();
            $totalSpoiledBoodBag = $spoiled->count();


            $data[] = [
                'total_donors' => $totalDonors,
                'total_deferrals' => $totalTempDeferral + $totalPermaDeferral,
                'total_dispensed' => $totalDispensed,
                'total_expired' => $totalExpired,
                'total_reactive' =>$totalReactiveBoodBag,
                'total_spoiled' => $totalSpoiledBoodBag,
            ];

            return response()->json([
                'status' => 'success',
                'data' => $data
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation failed',
                'errors' => $e->validator->errors(),
            ]);
        }
    }

    
    public function getBloodBagQuota(){
        
    }
    

    // public function countAllDonors() {
    //     $now = Carbon::now();

    //     $deferralsToUpdate = Deferral::where('end_date', '<=', $now)
    //     ->where('status', '!=', 1)
    //     ->get();

    //     foreach ($deferralsToUpdate as $deferral) {
    //         $deferral->status = 1;
    //         $deferral->save();

    //         $user_detail = UserDetail::where('user_id', $deferral->user_id)->first();
    //         if ($user_detail) {
    //             $user_detail->remarks = 0;
    //             $user_detail->save();
    //         }
    //     }

    //     $donors = UserDetail::join('users', 'user_details.user_id', '=', 'users.user_id')
    //     ->join('galloners', 'user_details.user_id', '=', 'galloners.user_id')
    //     ->where('user_details.remarks', 0)
    //     ->where('user_details.status', 0)
    //     ->where('galloners.donate_qty', '>', 0) 
    //     ->select('users.mobile', 'users.email', 'user_details.*', 'galloners.badge', 'galloners.donate_qty')
    //     ->count();
    
    //     return response()->json([
    //         'status' => 'success',
    //         'donorCount' => $donors
    //     ]);
    // }
    
    
    
    
    
    
}
