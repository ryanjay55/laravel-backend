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
            ->orderBy('blood_bags_id', 'desc')
            ->get();

        $data = [];

        foreach ($blood_bags as $blood_bag) {

            $data[] = [
                'date' => $blood_bag->created_at,
                'serial_number' => $blood_bag->serial_no,
                'collected' => $blood_bag->isCollected,
                'tested' => $blood_bag->isTested,
                'stored' => $blood_bag->isStored,
            ];
        }

        return response()->json($data);

    }
}
