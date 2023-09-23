<?php

namespace App\Http\Controllers\Api\Donor\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Galloner;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

    public function donorAvailableBlood(){
    
        $bloodBags = DB::table('user_details')
            ->leftJoin('blood_bags', 'user_details.user_id', '=', 'blood_bags.user_id')
            ->select('user_details.blood_type','blood_bags.serial_no', 'blood_bags.date_donated','bled_by')
            ->whereIn('user_details.blood_type', ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'])
            ->where('blood_bags.isStored', '=', 1)
            ->where('blood_bags.isExpired', '=', '0') 
            ->where('blood_bags.status', '=', '0')
            ->get();
    
        $bloodTypes = ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-'];
    
        $totalBloodBags = $bloodBags->count();
        $quotaPerQuaarter = 2250;
    
        $result = [];
    
        foreach ($bloodTypes as $bloodType) {
            $bloodBagsCount = $bloodBags->where('blood_type', $bloodType)->count();
            $legend = '';
    
            if ($bloodBagsCount <= 0) {
                $legend = 'Empty';
            } else {
                if ($totalBloodBags >= $quotaPerQuaarter) {
                    $legend = 'Normal';
                } else {
                    $availabilityPercentage = ($totalBloodBags / $quotaPerQuaarter) * 100; //to ge the perecntage of the available blood
    
                    if ($availabilityPercentage <= 10) {
                        $legend = 'Critically low';
                    } elseif ($availabilityPercentage <= 50) {
                        $legend = 'Low';
                    } else {
                        $legend = 'Normal';
                    }
                }
            }
    
            $result[] = [
                'blood_type' => $bloodType,
                'status' => $bloodBagsCount > 0 ? 'Available' : 'Unavailable',
                'legend' => $legend,
            ];
        }
    
        return response()->json([
            'blood_bags' => $result,
        ]);
    
    }
}
