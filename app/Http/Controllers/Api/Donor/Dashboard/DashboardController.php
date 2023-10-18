<?php

namespace App\Http\Controllers\Api\Donor\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Galloner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\Setting;

class DashboardController extends Controller
{
    public function getBadge()
    {

        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $galloners = Galloner::where('user_id', $userId)->first();

        return response()->json([
            'donation_count' => $galloners->donate_qty,
            'badge' => $galloners->badge
        ]); 
    }

    public function donorAvailableBlood()
    {
        $bloodBags = DB::table('user_details')
            ->leftJoin('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
            ->select('user_details.blood_type', 'blood_bags.serial_no', 'blood_bags.date_donated', 'bled_by')
            ->whereIn('user_details.blood_type', ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])
            ->where('blood_bags.isStored', '=', 1)
            ->where('blood_bags.isExpired', '=', '0')
            ->where('blood_bags.status', '=', '0')
            ->get();
    
        $bloodTypes = ['A+', 'B+', 'O+', 'AB+', 'A-', 'B-', 'O-', 'AB-'];
    
        // $settings = Setting::where('setting_desc', 'quarter_quota')->first();
        // $quotaPerQuarter = $settings->setting_value;
    
        $result = [];
    
        foreach ($bloodTypes as $bloodType) {
            $bloodBagsCount = $bloodBags->where('blood_type', $bloodType)->count();
            // $quota = $quotaPerQuarter / count($bloodTypes);
            // $availabilityPercentage = ($bloodBagsCount / $quota) * 100;
    
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
    
            $result[] = [
                'blood_type' => $bloodType,
                'status' => $bloodBagsCount > 0 ? 'Available' : 'Unavailable',
                'legend' => $legend,
                // 'percentage' => $availabilityPercentage,
            ];
        }
    
        return response()->json([
            'blood_bags' => $result,
        ]);
    }
}

// public function donorAvailableBlood(){
    
//     $bloodBags = DB::table('user_details')
//         ->leftJoin('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
//         ->select('user_details.blood_type','blood_bags.serial_no', 'blood_bags.date_donated','bled_by')
//         ->whereIn('user_details.blood_type', ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])
//         ->where('blood_bags.isStored', '=', 1)
//         ->where('blood_bags.isExpired', '=', '0') 
//         ->where('blood_bags.status', '=', '0')
//         ->get();

//     $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];

//     $settings = Setting::where('setting_desc','quarter_quota')->first();
//     $quotaPerQuarter = $settings->setting_value;

//     $result = [];

//     foreach ($bloodTypes as $bloodType) {
//         $bloodBagsCount = $bloodBags->where('blood_type', $bloodType)->count();
//         $quota = $quotaPerQuarter / count($bloodTypes);
//         $availabilityPercentage = ($bloodBagsCount / $quota) * 100;

//         $legend = '';

//         if ($bloodBagsCount <= 0) {
//             $legend = 'Empty';
//         } else {
//             if ($availabilityPercentage <= 10) {
//                 $legend = 'Critically low';
//             } elseif ($availabilityPercentage <= 50) {
//                 $legend = 'Low';
//             } else {
//                 $legend = 'Normal';
//             }
//         }

//         $result[] = [
//             'blood_type' => $bloodType,
//             'status' => $bloodBagsCount > 0 ? 'Available' : 'Unavailable',
//             'legend' => $legend,
//             'percentage' => $availabilityPercentage,
//         ];
//     }

//     return response()->json([
//         'blood_bags' => $result,
//     ]);
// }