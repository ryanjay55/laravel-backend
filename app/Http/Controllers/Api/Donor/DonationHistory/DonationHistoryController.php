<?php

namespace App\Http\Controllers\Api\Donor\DonationHistory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\BloodBag;
use Illuminate\Support\Facades\DB;

class DonationHistoryController extends Controller
{
    
    public function donationHistory()
    {
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $blood_bags = BloodBag::where('user_id', $userId)->get();

        $data = [];

        foreach ($blood_bags as $blood_bag) {
            $status = '';

            if ($blood_bag->isCollected === 1) {
                if ($blood_bag->isTested === 0 && $blood_bag->isStored === 0) {
                    $status = 'collected';
                } elseif ($blood_bag->isTested === 1 && $blood_bag->isStored === 0) {
                    $status = 'tested';
                } elseif ($blood_bag->isTested === 1 && $blood_bag->isStored === 1) {
                    $status = 'stored';
                }
            }
            
            $data[] = [
                'date' => $blood_bag->created_at,
                'serial_number' => $blood_bag->serial_no,
                'status' => $status,
            ];
        }

        return response()->json($data);
    }
}
