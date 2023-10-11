<?php

namespace App\Http\Controllers\Api\Admin\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\BloodBag;
use App\Models\Setting;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
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

    public function countBloodBagPerMonth(){
        $currentYear = date('Y');
        
        $bloodBags = DB::table('user_details')
            ->leftJoin('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
            ->select('user_details.blood_type', 'blood_bags.serial_no', 'blood_bags.date_donated', 'bled_by')
            ->whereIn('user_details.blood_type', ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])
            ->where('blood_bags.isStored', '=', 1)
            ->where('blood_bags.isExpired', '=', '0')
            ->where('blood_bags.status', '=', '0')
            ->where('user_details.remarks', '=', '0')
            ->whereYear('date_donated', $currentYear)
            ->get();
    
        $monthCounts = [];
    
        for ($i = 1; $i <= 12; $i++) {
            $monthName = date('F', mktime(0, 0, 0, $i, 1));
            $startDate = date('Y-m-d', strtotime($currentYear.'-'.$i.'-01'));
            $endDate = date('Y-m-t', strtotime($currentYear.'-'.$i.'-01'));
            $monthCount = $bloodBags->whereBetween('date_donated', [$startDate, $endDate])->count();
            $monthCounts[$monthName] = $monthCount;
        }
    
        return response()->json([
            'status' => 'success',
            'month_counts' => $monthCounts
        ]);
    }

    public function countDonorPerBarangay(){
        $donorsPerBarangay = DB::table('user_details')
            ->leftJoin('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
            ->select('user_details.barangay', DB::raw('count(*) as donor_count'))
            ->where('blood_bags.isCollected', '=', 1)
            ->where('user_details.municipality', '=', 'CITY OF VALENZUELA')
            ->groupBy('user_details.barangay')
            ->get();
    
        return response()->json([
            'status' => 'success',
            'donors_per_barangay' => $donorsPerBarangay
        ]);
    }

    public function mbdQuickView(){
        $data = [];
        $totalDonors = DB::table('user_details')
            ->leftJoin('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
            ->select('user_details.barangay', DB::raw('count(*) as donor_count'))
            ->where('blood_bags.isCollected', '=', 1)
            ->count();

        $totalTempDeferral = UserDetail::where('remarks','1')->count();
        $totalPermaDeferral = UserDetail::where('remarks','2')->count();
        $totalDeferral = $totalTempDeferral + $totalPermaDeferral;

        $totalDispensed = BloodBag::where('isUsed','1')->count();
        $totalExpired = BloodBag::where('isExpired','1')->count();

        $data[] = [
            'total_donors' => $totalDonors,
            'total_deferrals' => $totalDeferral,
            'total_dispensed' => $totalDispensed,
            'total_expired' => $totalExpired
        ];
        
        return response()->json([
            'status' => 'success',
            'data'  => $data
        ]);
    }
    
}
