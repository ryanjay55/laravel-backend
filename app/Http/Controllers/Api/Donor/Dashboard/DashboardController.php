<?php

namespace App\Http\Controllers\Api\Donor\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\BloodBag;
use App\Models\Galloner;
use App\Models\LastUpdate;
use App\Models\PatientReceiver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

use App\Models\Setting;

class DashboardController extends Controller
{
    public function donationSummary()
    {

        $user = Auth::user();
        $userId = $user->user_id;
        $galloners = Galloner::where('user_id', $userId)->first();
        $badge = $galloners->badge;
        $totalDonation = $galloners->donate_qty;
        $dispensedBlood = app(BloodBag::class)->countDispensedBlood($userId);
        $receivedBlood = app(PatientReceiver::class)->countReceivedBlood($userId);
        $nextBadge = '';

        if ($totalDonation >= 0 && $totalDonation <= 1) {
            $nextBadge = 'bronze';
            $donationsNeeded = 2 - $totalDonation;
        } elseif ($totalDonation >= 2 && $totalDonation <= 3) {
            $nextBadge = 'silver';
            $donationsNeeded = 4 - $totalDonation;
        } elseif ($totalDonation >= 4 && $totalDonation <= 7) {
            $nextBadge = 'gold';
            $donationsNeeded = 8 - $totalDonation;
        } elseif ($totalDonation >= 8) {
            $nextBadge = 'Already achieved the highest badge';
            $donationsNeeded = 0;
        }

        return [
            'status' => 'success',
            'totalDonation' => $totalDonation,
            'badge' => $badge,
            'dispensedBlood' => $dispensedBlood,
            'receivedBlood' => $receivedBlood,
            'nextBadge' => $nextBadge,
            'donationsNeeded' => $donationsNeeded,
        ];
    }

    public function donorAvailableBlood()
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

        // $settings = Setting::where('setting_desc', 'quarter_quota')->first();
        // $quotaPerQuarter = $settings->setting_value;

        $result = [];

        $latestCreatedAt = null; // Initialize a variable to store the latest created_at value

        // Find the latest created_at value
        $update = LastUpdate::first();

        if ($update) {
            $latestCreatedAt = $update->date_update;
            // Now you can use $latestCreatedAt
        } else {
            // Handle the case where $update is null, e.g., set a default value
            $latestCreatedAt = null; // or set it to some default date or value
        }


        // Format the latestCreatedAt
        $formattedLatestCreatedAt = $latestCreatedAt ? date('Y-m-d h:i A', strtotime($latestCreatedAt)) : null;
        if (!$formattedLatestCreatedAt || count($bloodBags) === 0) {
            // Set the formattedLatestCreatedAt to the current date and time
            $formattedLatestCreatedAt = date('Y-m-d h:i A');
        }

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
            'latest_created_at' => $formattedLatestCreatedAt, // Return the formatted value
        ]);
    }


}
