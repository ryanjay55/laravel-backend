<?php

namespace App\Http\Controllers\Api\Donor\DonationHistory;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\BloodBag;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Carbon\Carbon; 

class DonationHistoryController extends Controller
{
    
    public function donationHistory(Request $request)
    {
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $allBloodBags = BloodBag::where('user_id', $userId)
            ->orderBy('date_donated', 'desc') 
            ->get();
     
        $bloodBags = $allBloodBags->map(function ($blood_bag) {
            $status = '';

            if ($blood_bag->isCollected === 1 && $blood_bag->isStored === 0 && $blood_bag->isUsed === 0) {
                $status = 'Collected';
            } elseif ($blood_bag->isCollected === 1 && $blood_bag->isStored === 1 && $blood_bag->isUsed === 0) {
                $status = 'Stored';
            } else {
                $status = 'Used';
            }

            return [
                'date' => $blood_bag->date_donated,
                'serial_number' => $blood_bag->serial_no,
                'bled_by'   => $blood_bag->bled_by,
                'status' => $status,
            ];
        });

        // Paginate the results manually
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $perPage = 8; 

        $bloodBags = $bloodBags->slice(($currentPage - 1) * $perPage, $perPage);

        $bloodBags = new LengthAwarePaginator(
            $bloodBags,
            count($allBloodBags),
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath()]
        );

        // Return the paginated JSON response
        return response()->json([
            'status' => 'success',
            'data' => $bloodBags
        ]);
    }

    public function computeDaySinceLastDonation()
    {
        $user = getAuthenticatedUserId();
        $userId = $user->user_id;

        $donationHistory = BloodBag::where('user_id', $userId)
            ->orderBy('date_donated', 'desc')
            ->get();

        if ($donationHistory->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'No donation history available.',
                'days_since_last_donation' => 'You haven\'t donated yet'
            ]);
        }

        $mostRecentDonationDate = $donationHistory->first()->date_donated;

        $currentDate = Carbon::now(); // Get the current date and time
        $daysSinceLastDonation = $currentDate->diffInDays($mostRecentDonationDate);

        return response()->json([
            'status' => 'success',
            'days_since_last_donation' => 'Last donated ' . $daysSinceLastDonation . ' days ago'
        ]);
    }

    
}
