<?php

namespace App\Http\Controllers\Api\Donor\BloodJourney;

use App\Http\Controllers\Controller;
use App\Models\BloodBag;
use Illuminate\Http\Request;

class BloodJourneyController extends Controller
{
    public function bloodJourney(){

        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $blood_bags = BloodBag::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();

        $data = [];

        foreach ($blood_bags as $blood_bag) {

            $data[] = [
                'blood_bag_id' => $blood_bag->blood_bags_id,
                'date' => $blood_bag->date_donated,
                'serial_number' => $blood_bag->serial_no,
                'collected' => $blood_bag->isCollected,
                'dateCollected' => $blood_bag->date_collected,
                'tested' => $blood_bag->isTested,
                'dateTested' => $blood_bag->date_tested,
                'stored' => $blood_bag->isStored,
                'dateStored' => $blood_bag->date_stored
            ];
        }

        return response()->json([
            'status' => 'success',
            'bloodJourney' => $data

        ]);

    }
}
